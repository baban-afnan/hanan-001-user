<?php

namespace App\Http\Controllers\Verification;

use App\Http\Controllers\Controller;

use App\Helpers\ServiceManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\Verification;
use App\Models\Transaction;
use App\Models\Service;
use App\Models\Services1;
use App\Models\ServiceField;
use App\Models\Wallet;
use App\Repositories\NIN_PDF_Repository;
use Carbon\Carbon;

class NINverificationController extends Controller
{
    /**
     * Show NIN verification page
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        // Get Verification Service from DB
        $service = Services1::where('name', 'Verification')->first();
        
        // Get Prices
        $verificationPrice = 0;
        $standardSlipPrice = 0;
        $premiumSlipPrice = 0;
        $vninSlipPrice = 0;

        if ($service) {
            $verificationField = $service->fields()->where('field_code', '610')->first();
            $standardSlipField = $service->fields()->where('field_code', '611')->first();
            $premiumSlipField = $service->fields()->where('field_code', '612')->first();
            $vninSlipField = $service->fields()->where('field_code', '616')->first();

            $verificationPrice = $verificationField ? $verificationField->getPriceForUserType($user->role) : 0;
            $standardSlipPrice = $standardSlipField ? $standardSlipField->getPriceForUserType($user->role) : 0;
            $premiumSlipPrice = $premiumSlipField ? $premiumSlipField->getPriceForUserType($user->role) : 0;
            $vninSlipPrice = $vninSlipField ? $vninSlipField->getPriceForUserType($user->role) : 0;
        }

        $wallet = Wallet::where('user_id', $user->id)->first();

        return view('verification.nin-verification', [
            'wallet' => $wallet,
            'verificationPrice' => $verificationPrice,
            'standardSlipPrice' => $standardSlipPrice,
            'premiumSlipPrice' => $premiumSlipPrice,
            'vninSlipPrice' => $vninSlipPrice,
        ]);
    }

    /**
     * Store new NIN verification request
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'number_nin' => 'required|string|size:11|regex:/^[0-9]{11}$/',
        ]);

        // 1. Get Verification Service from DB
        $service = Services1::where('name', 'Verification')->first();

        if (!$service) {
            return back()->with([
                'status' => 'error',
                'message' => 'Verification service not available.'
            ]);
        }

        // 2. Get NIN Verification ServiceField (610)
        $serviceField = $service->fields()
            ->where('field_code', '610')
            ->where('is_active', true)
            ->first();

        if (!$serviceField) {
            return back()->with([
                'status' => 'error',
                'message' => 'NIN verification service is not available.'
            ]);
        }

        // 3. Determine service price based on user role
        $servicePrice = $serviceField->getPriceForUserType($user->role);

        // 4. Check wallet
        $wallet = Wallet::where('user_id', $user->id)->firstOrFail();

        if ($wallet->balance < $servicePrice) {
            return back()->with([
                'status' => 'error',
                'message' => 'Insufficient wallet balance. You need NGN ' . number_format($servicePrice - $wallet->balance, 2)
            ]);
        }

        try {
            $apiKey = env('AREWA_API_TOKEN');
            $apiBaseUrl = env('AREWA_BASE_URL');
            $apiUrl = rtrim($apiBaseUrl, '/') . '/nin/verify';

            $response = Http::withToken($apiKey)
                ->acceptJson()
                ->post($apiUrl, [
                    'nin' => $request->number_nin,
                ]);

            // Log the raw response for debugging
            Log::info('NIN Verification Response', [
                'status' => $response->status(),
                'response' => $response->json()
            ]);

            $decodedData = $response->json();

            if (!$response->successful() || (isset($decodedData['status']) && $decodedData['status'] === 'error')) {
                return back()->with([
                    'status' => 'error',
                    'message' => 'API Error: ' . ($decodedData['message'] ?? 'Unknown error occurred.')
                ]);
            }

            // Arewa Smart API usually returns success in 'status' field
            $status = $decodedData['status'] ?? 'UNKNOWN';

            if ($status === 'success') {
                // Check if NIN is suspended (contains **** in critical fields)
                $apiData = $decodedData['data'] ?? [];
                
                $isSuspended = false;
                $suspendedFields = ['firstname', 'surname', 'birthdate', 'birthstate', 'gender'];
                
                foreach ($suspendedFields as $field) {
                    $value = $apiData[$field] ?? ($apiData[str_replace('_', '', strtolower($field))] ?? '');
                    if (strpos($value, '****') !== false || strpos($value, '*****') !== false || $value === '*') {
                        $isSuspended = true;
                        break;
                    }
                }
                
                if ($isSuspended) {
                    return back()->with([
                        'status' => 'error',
                        'message' => 'This NIN is suspended and cannot be verified. Please contact NIMC for assistance.'
                    ]);
                }
                
                // Successful -> Charge + Create Transaction + Create Verification
                return $this->processSuccessTransaction(
                    $wallet,
                    $servicePrice,
                    $user,
                    $serviceField,
                    $service,
                    $decodedData
                );
            } else {
                return back()->with([
                    'status' => 'error',
                    'message' => $decodedData['message'] ?? 'Verification failed.'
                ]);
            }

        } catch (\Exception $e) {
             // System/Network Error -> No Charge + Transaction Log if possible (optional, but good for tracking)
             // For now, adhering to returning back with error, but we could log a failed transaction here too if needed.
             // Given the catch block scope, we might not have serviceField context easily if it failed before fetching it.
            return back()->with([
                'status' => 'error',
                'message' => 'System Error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Process successful transaction (Charge + Verification Record)
     */
    private function processSuccessTransaction($wallet, $servicePrice, $user, $serviceField, $service, $ninData)
    {
        DB::beginTransaction();

        try {
            $transactionRef = 'Ver-' . (time() % 1000000000) . '-' . mt_rand(100, 999);
            $performedBy = $user->first_name . ' ' . $user->last_name;

            $transaction = Transaction::create([
                'referenceId' => $transactionRef,
                'user_id' => $user->id,
                'amount' => $servicePrice,
                'service_type'    => 'NIN Verification',
                'service_description' => "NIN Verification - {$serviceField->field_name}",
                'type' => 'debit',
                'status' => 'Approved',
                'performed_by'    => $performedBy,
                'metadata' => [
                    'service' => 'verification',
                    'service_field' => $serviceField->field_name,
                    'field_code' => $serviceField->field_code,
                    'nin' => $ninData['data']['nin'] ?? 'N/A', // Should exist on success
                    'user_role' => $user->role,
                    'price_details' => [
                        'base_price' => $serviceField->base_price,
                        'user_price' => $servicePrice,
                    ],
                    'source' => 'API',
                    'api_response' => $ninData
                ],
            ]);

            // Deduct wallet balance
            $wallet->decrement('balance', $servicePrice);

            $apiData = $ninData['data'] ?? [];

            Verification::create([
                'user_id' => $user->id,
                'service_field_id' => $serviceField->id,
                'service_id' => $service->id,
                'transaction_id' => $transaction->id,
                'reference' => $transactionRef,
                'idno' => $apiData['nin'] ?? ($apiData['number_nin'] ?? ''),
                'number_nin' => $apiData['nin'] ?? ($apiData['number_nin'] ?? ''),
                'firstname' => $apiData['firstName'] ?? ($apiData['first_name'] ?? ''),
                'middlename' => $apiData['middleName'] ?? ($apiData['middle_name'] ?? ''),
                'surname' => $apiData['surname'] ?? ($apiData['last_name'] ?? ''),
                'birthdate' =>  $apiData['birthDate'] ?? ($apiData['dob'] ?? ($apiData['birthday'] ?? '')),
                'gender' => $apiData['gender'] ?? '',
                'telephoneno' => $apiData['telephoneNo'] ?? ($apiData['phone'] ?? ($apiData['phoneNumber'] ?? '')),
                'photo_path' => $apiData['photo'] ?? '',
                'performed_by'    => $performedBy,
                'submission_date' => Carbon::now()
            ]);

            DB::commit();

            // Flash normalized verification data for Blade
            session()->flash('verification', $ninData);

            return redirect()->route('user.nin.verification.index')->with([
                'status' => 'success',
                'message' => "NIN Verification successful. Reference: {$transactionRef}. Charged: NGN " . number_format($servicePrice, 2),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            report($e);
            return back()->with([
                'status' => 'error',
                'message' => 'Transaction failed: ' . $e->getMessage()
            ]);
        }
    }


    /**
     * Charge for Slip Download
     */
    private function chargeForSlip($user, $fieldCode)
    {
         // 1. Get Verification Service from DB
         $service = Services1::where('name', 'Verification')->first();

        if (!$service) {
            throw new \Exception('Verification service not available.');
        }

        // 2. Get ServiceField
        $serviceField = $service->fields()
            ->where('field_code', $fieldCode)
            ->where('is_active', true)
            ->first();

        if (!$serviceField) {
             throw new \Exception('Slip service not available.');
        }

        // 3. Determine service price based on user role
        $servicePrice = $serviceField->getPriceForUserType($user->role);

        // 4. Check wallet
        $wallet = Wallet::where('user_id', $user->id)->firstOrFail();

        if ($wallet->balance < $servicePrice) {
             throw new \Exception('Insufficient wallet balance.');
        }
        
        DB::beginTransaction();
        try {
             $transactionRef = 'Slip-' . (time() % 1000000000) . '-' . mt_rand(100, 999);
             $performedBy = $user->first_name . ' ' . $user->last_name;
 

            $transaction = Transaction::create([
                'referenceId' => $transactionRef,
                'user_id' => $user->id,
                'amount' => $servicePrice,
                'service_type' => 'Slip Download',
                'service_description' => "Slip Download - {$serviceField->field_name}",
                'type' => 'debit',
                'status' => 'Approved',
                'performed_by' => $performedBy,
                'metadata' => [
                    'service' => 'slip_download',
                    'service_field' => $serviceField->field_name,
                    'field_code' => $serviceField->field_code,
                    'user_role' => $user->role,
                    'price_details' => [
                        'base_price' => $serviceField->base_price,
                        'user_price' => $servicePrice,
                    ],
                    'source' => 'Manual',
                ],
            ]);
 
             // Deduct wallet balance
             $wallet->decrement('balance', $servicePrice);
             
             DB::commit();
             return true;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Download NIN slips
     */
    public function standardSlip($nin_no)
    {
        try {
            $this->chargeForSlip(Auth::user(), '611'); // Charge for Standard Slip
            
            $repObj = new NIN_PDF_Repository();
            return $repObj->standardPDF($nin_no);
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function premiumSlip($nin_no)
    {
        try {
            $this->chargeForSlip(Auth::user(), '612'); // Charge for Premium Slip
            
            $repObj = new NIN_PDF_Repository();
            return $repObj->premiumPDF($nin_no);
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function vninSlip($nin_no)
    {
        try {
            $this->chargeForSlip(Auth::user(), '616'); // Charge for VNIN Slip
            
            $repObj = new NIN_PDF_Repository();
            return $repObj->vninPDF($nin_no);
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
