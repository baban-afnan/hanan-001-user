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
use App\Repositories\BVN_PDF_Repository;
use Carbon\Carbon;

class BvnverificationController extends Controller
{

    /**
     * Show BVN verification page
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
        $plasticSlipPrice = 0;

        if ($service) {
            $verificationField = $service->fields()->where('field_code', '600')->first();
            $standardSlipField = $service->fields()->where('field_code', '601')->first();
            $premiumSlipField = $service->fields()->where('field_code', '602')->first();
            $plasticSlipField = $service->fields()->where('field_code', '603')->first();

            $verificationPrice = $verificationField ? $verificationField->getPriceForUserType($user->role) : 0;
            $standardSlipPrice = $standardSlipField ? $standardSlipField->getPriceForUserType($user->role) : 0;
            $premiumSlipPrice = $premiumSlipField ? $premiumSlipField->getPriceForUserType($user->role) : 0;
            $plasticSlipPrice = $plasticSlipField ? $plasticSlipField->getPriceForUserType($user->role) : 0;
        }

        $wallet = Wallet::where('user_id', $user->id)->first();

        return view('verification.bvn-verification', [
            'wallet' => $wallet,
            'verificationPrice' => $verificationPrice,
            'standardSlipPrice' => $standardSlipPrice,
            'premiumSlipPrice' => $premiumSlipPrice,
            'plasticSlipPrice' => $plasticSlipPrice,
        ]);
    }

    /**
     * Store new BVN verification request
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'bvn' => 'required|string|size:11|regex:/^[0-9]{11}$/',
        ]);

        // 1. Get Verification Service from DB
        $service = Services1::where('name', 'Verification')->first();

        if (!$service) {
            return back()->with([
                'status' => 'error',
                'message' => 'Verification service not available.'
            ]);
        }

        // 2. Get BVN Verification ServiceField (600)
        $serviceField = $service->fields()
            ->where('field_code', '600')
            ->where('is_active', true)
            ->first();

        if (!$serviceField) {
            return back()->with([
                'status' => 'error',
                'message' => 'BVN verification service is not available.'
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
            $apiUrl = rtrim($apiBaseUrl, '/') . '/bvn/verify';

            $response = Http::withoutVerifying()
                ->withToken($apiKey)
                ->acceptJson()
                ->post($apiUrl, [
                    'bvn' => $request->bvn,
                ]);

            // Log the raw response for debugging
            Log::info('BVN Verification Response', [
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
                // If status is not success but response was successful, treat as failed but record it if needed.
                // Based on common patterns, Arewa API uses 'success'/'error'.
                return back()->with([
                    'status' => 'error',
                    'message' => $decodedData['message'] ?? 'Verification failed.'
                ]);
            }

        } catch (\Exception $e) {
             return back()->with([
                'status' => 'error',
                'message' => 'System Error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Process successful transaction (Charge + Verification Record)
     */
    private function processSuccessTransaction($wallet, $servicePrice, $user, $serviceField, $service, $bvnData)
    {
        DB::beginTransaction();

        try {

            $transactionRef = 'Ver-' . (time() % 1000000000) . '-' . mt_rand(100, 999);
            $performedBy = $user->first_name . ' ' . $user->last_name;

            $transaction = Transaction::create([
                'referenceId' => $transactionRef,
                'user_id' => $user->id,
                'amount' => $servicePrice,
                'service_type'    => 'BVN Verification',
                'service_description' => "BVN Verification - {$serviceField->field_name}",
                'type' => 'debit',
                'status' => 'Approved',
                'performed_by'    => $performedBy,
                'metadata' => [
                    'service' => 'verification',
                    'service_field' => $serviceField->field_name,
                    'field_code' => $serviceField->field_code,
                    'bvn' => $bvnData['data']['bvn'] ?? 'N/A',
                    'user_role' => $user->role,
                    'price_details' => [
                        'base_price' => $serviceField->base_price,
                        'user_price' => $servicePrice,
                    ],
                    'source' => 'API',
                    'api_response' => $bvnData
                ],
            ]);

            // Deduct wallet balance
            $wallet->decrement('balance', $servicePrice);

            $apiData = $bvnData['data'] ?? [];

            Verification::create([
                'user_id' => $user->id,
                'service_field_id' => $serviceField->id,
                'service_id' => $service->id,
                'transaction_id' => $transaction->id,
                'reference' => $transactionRef,
                'idno' => $apiData['bvn'] ?? '',
                'firstname' => $apiData['firstName'] ?? ($apiData['first_name'] ?? ''),
                'middlename' => $apiData['middleName'] ?? ($apiData['middle_name'] ?? ''),
                'surname' => $apiData['lastName'] ?? ($apiData['last_name'] ?? ''),
                'birthdate' =>  $apiData['birthday'] ?? ($apiData['dob'] ?? ''),
                'gender' => $apiData['gender'] ?? '',
                'maritalstatus' => $apiData['maritalStatus'] ?? '',
                'email' => $apiData['email'] ?? '',
                'telephoneno' => $apiData['phoneNumber'] ?? ($apiData['phone'] ?? ''),
                'photo_path' => $apiData['photo'] ?? '',
                'enrollment_bank' => $apiData['enrollmentBank'] ?? '',
                'enrollment_branch' => $apiData['enrollmentBranch'] ?? '',
                'registration_date' => $apiData['registrationDate'] ?? '',
                'self_origin_state' => $apiData['stateOfOrigin'] ?? '',
                'self_origin_lga' => $apiData['lgaOfOrigin'] ?? '',
                'residence_state' => $apiData['stateOfResidence'] ?? '',
                'residence_lga' => $apiData['lgaOfResidence'] ?? '',
                'residence_address' => $apiData['residentialAddress'] ?? '',
                'response_data' => $apiData,
                'performed_by'    => $performedBy,
                'submission_date' => Carbon::now()
            ]);

            DB::commit();

            // Flash normalized verification data for Blade
            session()->flash('verification', $bvnData);

            return redirect()->route('user.bvn-verification')->with([
                'status' => 'success',
                'message' => "BVN Verification successful. Reference: {$transactionRef}. Charged: NGN " . number_format($servicePrice, 2),
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
            throw new \Exception('Insufficient wallet balance. Price: ₦' . number_format($servicePrice, 2) . ', Balance: ₦' . number_format($wallet->balance, 2));
        }

        $transactionRef = 'Slip-' . (time() % 1000000000) . '-' . mt_rand(100, 999);

        Transaction::create([
            'referenceId' => $transactionRef,
            'user_id' => $user->id,
            'amount' => $servicePrice,
            'service_type' => 'Slip Download',
            'service_description' => "Slip Download: {$serviceField->field_name}",
            'type' => 'debit',
            'status' => 'Approved',
        ]);

        // Deduct wallet balance
        $wallet->decrement('balance', $servicePrice);
        return $servicePrice;
    }

    /**
     * Download PDF slips
     */
    public function standardBVN($bvn_no)
    {
        $user = Auth::user();
        $veridiedRecord = Verification::where('user_id', $user->id)
            ->where(function($q) use ($bvn_no) {
                $q->where('idno', $bvn_no)
                  ->orWhere('bvn', $bvn_no)
                  ->orWhere('id', $bvn_no);
            })
            ->latest()
            ->first();

        if (!$veridiedRecord) {
            return response()->json([
                "message" => "Verification record not found or unauthorized access.",
                "errors" => ["Not Found" => "Verification record not found or unauthorized access."]
            ], 404);
        }

        DB::beginTransaction();
        try {
            $this->chargeForSlip($user, '601'); // Charge for Standard Slip
            $view = view('freeBVN', ['veridiedRecord' => $veridiedRecord, 'verifiedRecord' => $veridiedRecord])->render();
            DB::commit();
            return response()->json(['view' => $view]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                "message" => $e->getMessage(),
                "errors" => ["Charge Failed" => $e->getMessage()]
            ], 422);
        }
    }

    public function premiumBVN($bvn_no)
    {
        $user = Auth::user();
        $veridiedRecord = Verification::where('user_id', $user->id)
            ->where(function($q) use ($bvn_no) {
                $q->where('idno', $bvn_no)
                  ->orWhere('bvn', $bvn_no)
                  ->orWhere('id', $bvn_no);
            })
            ->latest()
            ->first();

        if (!$veridiedRecord) {
            return response()->json([
                "message" => "Verification record not found or unauthorized access.",
                "errors" => ["Not Found" => "Verification record not found or unauthorized access."]
            ], 404);
        }

        DB::beginTransaction();
        try {
            $this->chargeForSlip($user, '602'); // Charge for Premium Slip
            $view = view('PremiumBVN', ['veridiedRecord' => $veridiedRecord, 'verifiedRecord' => $veridiedRecord])->render();
            DB::commit();
            return response()->json(['view' => $view]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                "message" => $e->getMessage(),
                "errors" => ["Charge Failed" => $e->getMessage()]
            ], 422);
        }
    }

    public function plasticBVN($bvn_no)
    {
        $user = Auth::user();
        $veridiedRecord = Verification::where('user_id', $user->id)
            ->where(function($q) use ($bvn_no) {
                $q->where('idno', $bvn_no)
                  ->orWhere('bvn', $bvn_no)
                  ->orWhere('id', $bvn_no);
            })
            ->latest()
            ->first();

        if (!$veridiedRecord) {
            if (request()->expectsJson() || request()->ajax() || request()->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json(['message' => 'Verification record not found or unauthorized access.'], 404);
            }
            return back()->with('error', 'Verification record not found or unauthorized access.');
        }

        DB::beginTransaction();
        try {
            $this->chargeForSlip($user, '603'); // Charge for Plastic Slip
            
            $repObj = new BVN_PDF_Repository();
            $targetBvn = $veridiedRecord->idno ?? $veridiedRecord->bvn ?? $bvn_no;
            $pdf = $repObj->plasticPDF($targetBvn);
            DB::commit();
            return $pdf;
        } catch (\Exception $e) {
            DB::rollBack();
            if (request()->expectsJson() || request()->ajax() || request()->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json(['message' => $e->getMessage()], 422);
            }
            return back()->with('error', $e->getMessage());
        }
    }
}
