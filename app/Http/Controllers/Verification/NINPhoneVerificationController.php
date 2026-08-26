<?php

namespace App\Http\Controllers\Verification;

use App\Http\Controllers\Controller;

use App\Helpers\ServiceManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use App\Models\Verification;
use App\Models\Transaction;
use App\Models\Service;
use App\Models\Services1;
use App\Models\ServiceField;
use App\Models\Wallet;
use App\Repositories\NIN_PDF_Repository;
use Carbon\Carbon;
use Illuminate\Support\Str;

class NINPhoneVerificationController extends Controller
{
    /**
     * Show Phone verification page
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        // Get Verification Service from DB
        $service = Services1::where('name', 'Verification')->first();
        
        // Get Prices
        $phonePrice = 0;
        $regularSlipPrice = 0;
        $standardSlipPrice = 0;
        $premiumSlipPrice = 0;

        if ($service) {
            $phoneField = $service->fields()->where('field_code', 'V105')->first();
            $regularField = $service->fields()->where('field_code', 'V102')->first();
            $standardField = $service->fields()->where('field_code', '611')->first();
            $premiumField = $service->fields()->where('field_code', '612')->first();

            $vninField = $service->fields()->where('field_code', '616')->first();

            $basicField = $service->fields()->where('field_code', 'V101')->first();
            $vninField = $service->fields()->where('field_code', '616')->first();

            $phonePrice = $phoneField ? $phoneField->getPriceForUserType($user->role) : 0;
            $basicSlipPrice = $basicField ? $basicField->getPriceForUserType($user->role) : 0;
            $regularSlipPrice = $regularField ? $regularField->getPriceForUserType($user->role) : 0;
            $standardSlipPrice = $standardField ? $standardField->getPriceForUserType($user->role) : 0;
            $premiumSlipPrice = $premiumField ? $premiumField->getPriceForUserType($user->role) : 0;
            $vninSlipPrice = $vninField ? $vninField->getPriceForUserType($user->role) : 0;
        }

        $wallet = Wallet::where('user_id', $user->id)->first();

        return view('verification.nin-phone-verification', [
            'wallet' => $wallet,
            'phonePrice' => $phonePrice,
            'basicSlipPrice' => $basicSlipPrice,
            'regularSlipPrice' => $regularSlipPrice,
            'standardSlipPrice' => $standardSlipPrice,
            'premiumSlipPrice' => $premiumSlipPrice,
            'vninSlipPrice' => $vninSlipPrice ?? 0,
        ]);
    }

    /**
     * Store new Phone verification request
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'phone_number' => 'required|string|size:11|regex:/^[0-9]{11}$/',
        ]);

        // 1. Get Verification Service from DB
        $service = Services1::where('name', 'Verification')->first();

        if (!$service) {
            return back()->with([
                'status' => 'error',
                'message' => 'Verification service not available.'
            ]);
        }

        // 2. Get ServiceField (V105)
        $serviceField = $service->fields()
            ->where('field_code', 'V105')
            ->where('is_active', true)
            ->first();

        if (!$serviceField) {
            return back()->with([
                'status' => 'error',
                'message' => 'Phone verification service is not available.'
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
            $baseUrl = env('AREWA_BASE_URL');
            $url = rtrim($baseUrl, '/') . '/nin/phone';

            $payload = [
                'value' => $request->phone_number,
                'ref' => 'REF-' . Str::random(10),
            ];

            $response = Http::withoutVerifying()
                ->withToken($apiKey)
                ->acceptJson()
                ->timeout(30)
                ->post($url, $payload);

            $data = $response->json();

            // Check for successful response
            if ($response->successful() && isset($data['status']) && $data['status'] === true) {
                if (isset($data['api_response']['status']) && $data['api_response']['status'] === true) {
                     return $this->processSuccessTransaction(
                        $wallet,
                        $servicePrice,
                        $user,
                        $serviceField,
                        $service,
                        $data
                    );
                }
            }

            // Handle different error scenarios
            $errorMessage = $data['message'] ?? 'Verification failed. Please try again.';
            
            // Check if it's an upstream provider error
            if (isset($data['message']) && (
                str_contains(strtolower($data['message']), 'upstream') ||
                str_contains(strtolower($data['message']), 'nimc') ||
                str_contains(strtolower($data['message']), 'unavailable') ||
                str_contains(strtolower($data['message']), 'service is currently')
            )) {
                \Log::warning('NIMC Service Unavailable', [
                    'phone' => $request->phone_number,
                    'user_id' => $user->id,
                    'response' => $data
                ]);
                $errorMessage .= ' (Temporary issue with service provider)';
            } else {
                // Log API errors for debugging
                \Log::error('NIN Phone Verification API Error', [
                    'phone' => $request->phone_number,
                    'user_id' => $user->id,
                    'status_code' => $response->status(),
                    'response' => $data
                ]);
            }

            return $this->processFailedTransaction(
                $wallet,
                $servicePrice,
                $user,
                $serviceField,
                $service,
                $request->phone_number,
                $errorMessage
            );

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            \Log::error('NIN Phone Verification Connection Error', [
                'phone' => $request->phone_number ?? 'N/A',
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            
            return $this->processFailedTransaction(
                $wallet,
                $servicePrice,
                $user,
                $serviceField,
                $service,
                $request->phone_number,
                'Unable to connect to verification service. Request timed out.'
            );
        } catch (\Illuminate\Http\Client\RequestException $e) {
            \Log::error('NIN Phone Verification Request Error', [
                'phone' => $request->phone_number ?? 'N/A',
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            
            return $this->processFailedTransaction(
                $wallet,
                $servicePrice,
                $user,
                $serviceField,
                $service,
                $request->phone_number,
                'Verification request failed.'
            );
        } catch (\Exception $e) {
            \Log::error('NIN Phone Verification System Error', [
                'phone' => $request->phone_number ?? 'N/A',
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->processFailedTransaction(
                $wallet,
                $servicePrice,
                $user,
                $serviceField,
                $service,
                $request->phone_number,
                'System error occurred during verification.'
            );
        }
    }

    /**
     * Process failed transaction (Charge + Failed Verification Record)
     */
    private function processFailedTransaction($wallet, $servicePrice, $user, $serviceField, $service, $phoneNumber, $errorMessage)
    {
        DB::beginTransaction();

        try {
            $transactionRef = 'P2' . (time() % 1000000000) . '-' . mt_rand(100, 999);
            $performedBy = $user->first_name . ' ' . $user->last_name;

            $transaction = Transaction::create([
                'referenceId' => $transactionRef,
                'user_id' => $user->id,
                'amount' => $servicePrice,
                'service_type'    => 'nin phone verification',
                'service_description' => "NIN Phone Verification - {$serviceField->field_name} (Failed)",
                'type' => 'debit',
                'status' => 'Approved',
            ]);

            // Deduct wallet balance
            $wallet->decrement('balance', $servicePrice);

            Verification::create([
                'user_id' => $user->id,
                'service_field_id' => $serviceField->id,
                'service_id' => $service->id,
                'transaction_id' => $transaction->id,
                'reference' => $transactionRef,
                'field_code' => $serviceField->field_code ?? null,
                'field_name' => $serviceField->field_name ?? null,
                'service_name' => $service->service_name ?? null,
                'service_type' => $service->service_type ?? null,
                'amount' => $servicePrice,
                'telephoneno' => $phoneNumber,
                'performed_by' => $performedBy,
                'submission_date' => Carbon::now(),
                'status' => 'failed',
            ]);

            DB::commit();

            return back()->with([
                'status' => 'error',
                'message' => "Verification failed: {$errorMessage} Reference: {$transactionRef}. Charged: NGN " . number_format($servicePrice, 2),
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
     * Process successful transaction (Charge + Verification Record)
     */
    private function processSuccessTransaction($wallet, $servicePrice, $user, $serviceField, $service, $apiResponse)
    {
        DB::beginTransaction();

        try {
            // Extract data from API response - handle both array and single object
            $dataArray = $apiResponse['api_response']['data']['data'] ?? [];
            
            // Get the first record if it's an array, otherwise use empty array
            $ninData = [];
            if (is_array($dataArray) && !empty($dataArray)) {
                $ninData = isset($dataArray[0]) ? $dataArray[0] : $dataArray;
            }
            
            // Log if no data received but continue with transaction
            if (empty($ninData)) {
                \Log::warning('NIN Phone Verification - No data in response', [
                    'user_id' => $user->id,
                    'response' => $apiResponse
                ]);
            }
            
            $transactionRef = 'P2' . (time() % 1000000000) . '-' . mt_rand(100, 999);
            $performedBy = $user->first_name . ' ' . $user->last_name;

            $transaction = Transaction::create([
                'referenceId' => $transactionRef,
                'user_id' => $user->id,
                'amount' => $servicePrice,
                'service_type'    => 'nin phone verification',
                'service_description' => "NIN Phone Verification - {$serviceField->field_name}",
                'type' => 'debit',
                'status' => 'Approved',
            ]);

            // Deduct wallet balance
            $wallet->decrement('balance', $servicePrice);

            // Map all fields from API response to verification table
            Verification::create([
                'user_id' => $user->id,
                'service_field_id' => $serviceField->id,
                'service_id' => $service->id,
                'transaction_id' => $transaction->id,
                'reference' => $transactionRef,
                'field_code' => $serviceField->field_code ?? null,
                'field_name' => $serviceField->field_name ?? null,
                'service_name' => $service->service_name ?? null,
                'service_type' => $service->service_type ?? null,
                'amount' => $servicePrice,
                
                // Personal Information
                'firstname' => $ninData['firstname'] ?? null,
                'middlename' => $ninData['middlename'] ?? null,
                'surname' => $ninData['surname'] ?? null,
                'gender' => $ninData['gender'] ?? null,
                'birthdate' => $ninData['birthdate'] ?? null,
                'birthstate' => $ninData['birthstate'] ?? null,
                'birthlga' => $ninData['birthlga'] ?? null,
                'birthcountry' => $ninData['birthcountry'] ?? null,
                'maritalstatus' => $ninData['maritalstatus'] ?? null,
                'email' => $ninData['email'] ?? null,
                'telephoneno' => $ninData['telephoneno'] ?? null,
                
                // Residence Information
                'residence_address' => $ninData['residence_AdressLine1'] ?? null,
                'residence_state' => $ninData['residence_state'] ?? null,
                'residence_lga' => $ninData['residence_lga'] ?? null,
                'residence_town' => $ninData['residence_Town'] ?? null,
                
                // Additional Information
                'religion' => $ninData['religion'] ?? null,
                'employmentstatus' => $ninData['emplymentstatus'] ?? null,
                'educationallevel' => $ninData['educationallevel'] ?? null,
                'profession' => $ninData['profession'] ?? null,
                'height' => $ninData['heigth'] ?? null,
                'title' => $ninData['title'] ?? null,
                
                // NIN and Identification
                'nin' => $ninData['nin'] ?? null,
                'idno' => $ninData['nin'] ?? null,
                'number_nin' => $ninData['nin'] ?? null,
                'userid' => $ninData['centralID'] ?? null,
                'photo_path' => $ninData['photo'] ?? null,
                'signature_path' => $ninData['signature'] ?? null,
                'trackingId' => $ninData['trackingId'] ?? null,
                
                // Next of Kin Information
                'nok_firstname' => $ninData['nok_firstname'] ?? null,
                'nok_middlename' => $ninData['nok_middlename'] ?? null,
                'nok_surname' => $ninData['nok_surname'] ?? null,
                'nok_address1' => $ninData['nok_address1'] ?? null,
                'nok_address2' => $ninData['nok_address2'] ?? null,
                'nok_lga' => $ninData['nok_lga'] ?? null,
                'nok_state' => $ninData['nok_state'] ?? null,
                'nok_town' => $ninData['nok_town'] ?? null,
                'nok_postalcode' => $ninData['nok_postalcode'] ?? null,
                
                // Self Origin Information
                'self_origin_state' => $ninData['self_origin_state'] ?? null,
                'self_origin_lga' => $ninData['self_origin_lga'] ?? null,
                'self_origin_place' => $ninData['self_origin_place'] ?? null,
                
                // Transaction Information
                'performed_by' => $performedBy,
                'submission_date' => Carbon::now(),
                'status' => 'successful',
            ]);

            DB::commit();

            session()->flash('verification', [
                'data' => [
                    'nin' => $ninData['nin'] ?? 'N/A',
                    'firstName' => $ninData['firstname'] ?? 'N/A',
                    'surname' => $ninData['surname'] ?? 'N/A',
                    'middleName' => $ninData['middlename'] ?? 'N/A',
                    'birthDate' => $ninData['birthdate'] ?? 'N/A',
                    'gender' => $ninData['gender'] ?? 'N/A',
                    'telephoneNo' => $ninData['telephoneno'] ?? 'N/A',
                    'photo' => $ninData['photo'] ?? null,
                ]
            ]);

            return redirect()->route('user.nin.phone.index')->with([
                'status' => 'success',
                'message' => "NIN Phone Verification successful. Reference: {$transactionRef}. Charged: NGN " . number_format($servicePrice, 2),
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
         $service = Services1::where('name', 'Verification')->first();

        if (!$service) {
            throw new \Exception('Verification service not available.');
        }

        $serviceField = $service->fields()
            ->where('field_code', $fieldCode)
            ->where('is_active', true)
            ->first();

        if (!$serviceField) {
             throw new \Exception('Slip service not available.');
        }

        $servicePrice = $serviceField->getPriceForUserType($user->role);
        $wallet = Wallet::where('user_id', $user->id)->firstOrFail();

        if ($wallet->balance < $servicePrice) {
             throw new \Exception('Insufficient wallet balance.');
        }
        
        DB::beginTransaction();
        try {
             $transactionRef = 'Slip-' . (time() % 1000000000) . '-' . mt_rand(100, 999);
             $performedBy = $user->first_name . ' ' . $user->last_name;
 
             Transaction::create([
                 'referenceId' => $transactionRef,
                 'user_id' => $user->id,
                 'amount' => $servicePrice,
                 'service_type' => 'Slip Download',
                 'service_description' => "Slip Download: {$serviceField->field_name}",
                 'type' => 'debit',
                 'status' => 'Approved',
             ]);
 
             $wallet->decrement('balance', $servicePrice);
             
             DB::commit();
             return true;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function basicSlip($nin_no)
    {
        try {
            $this->chargeForSlip(Auth::user(), 'V101');
            $repObj = new NIN_PDF_Repository();
            return $repObj->basicPDF($nin_no);
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function regularSlip($nin_no)
    {
        try {
            $this->chargeForSlip(Auth::user(), 'V102');
            $repObj = new NIN_PDF_Repository();
            return $repObj->regularPDF($nin_no);
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function standardSlip($nin_no)
    {
        try {
            $this->chargeForSlip(Auth::user(), '611');
            $repObj = new NIN_PDF_Repository();
            return $repObj->standardPDF($nin_no);
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function premiumSlip($nin_no)
    {
        try {
            $this->chargeForSlip(Auth::user(), '612');
            $repObj = new NIN_PDF_Repository();
            return $repObj->premiumPDF($nin_no);
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function vninSlip($nin_no)
    {
        try {
            $this->chargeForSlip(Auth::user(), '616');
            $repObj = new NIN_PDF_Repository();
            return $repObj->vninPDF($nin_no);
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
