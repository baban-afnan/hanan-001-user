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

class NINDemoVerificationController extends Controller
{
    /**
     * Show Demographic verification page
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        // Get Verification Service from DB
        $service = Services1::where('name', 'Verification')->first();
        
        // Get Prices
        $demoPrice = 0;
        $freeSlipPrice = 0;
        $regularSlipPrice = 0;
        $standardSlipPrice = 0;
        $premiumSlipPrice = 0;

        if ($service) {
            $demoField = $service->fields()->where('field_code', 'V100')->first();
            $freeField = $service->fields()->where('field_code', 'V101')->first();
            $regularField = $service->fields()->where('field_code', 'V102')->first();
            $standardField = $service->fields()->where('field_code', '611')->first();
            $premiumField = $service->fields()->where('field_code', '612')->first();

            $vninField = $service->fields()->where('field_code', '616')->first();

            $demoPrice = $demoField ? $demoField->getPriceForUserType($user->role) : 0;
            $freeSlipPrice = $freeField ? $freeField->getPriceForUserType($user->role) : 0;
            $regularSlipPrice = $regularField ? $regularField->getPriceForUserType($user->role) : 0;
            $standardSlipPrice = $standardField ? $standardField->getPriceForUserType($user->role) : 0;
            $premiumSlipPrice = $premiumField ? $premiumField->getPriceForUserType($user->role) : 0;
            $vninSlipPrice = $vninField ? $vninField->getPriceForUserType($user->role) : 0;
        }

        $wallet = Wallet::where('user_id', $user->id)->first();

        return view('verification.nin-demo-verification', [
            'wallet' => $wallet,
            'demoPrice' => $demoPrice,
            'basicSlipPrice' => $freeSlipPrice,
            'regularSlipPrice' => $regularSlipPrice,
            'standardSlipPrice' => $standardSlipPrice,
            'premiumSlipPrice' => $premiumSlipPrice,
            'vninSlipPrice' => $vninSlipPrice ?? 0,
        ]);
    }

    /**
     * Store new Demographic verification request
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'firstName' => 'required|string',
            'lastName' => 'required|string',
            'gender' => 'required|string|in:M,F',
            'dateOfBirth' => 'required|string', 
        ]);

        // 1. Get Verification Service from DB
        $service = Services1::where('name', 'Verification')->first();

        if (!$service) {
            return back()->with([
                'status' => 'error',
                'message' => 'Verification service not available.'
            ]);
        }

        // 2. Get ServiceField (V100)
        $serviceField = $service->fields()
            ->where('field_code', 'V100')
            ->where('is_active', true)
            ->first();

        if (!$serviceField) {
            return back()->with([
                'status' => 'error',
                'message' => 'Demographic verification service is not available.'
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
            $url = rtrim($baseUrl, '/') . '/nin/demo';

            $payload = [
                'firstName' => $request->firstName,
                'lastName' => $request->lastName,
                'gender' => $request->gender,
                'dateOfBirth' => $request->dateOfBirth,
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
            $errorMessage = $data['message'] ?? 'Verification failed. Please check your details and try again.';
            
            // Check if it's an upstream provider error
            if (isset($data['message']) && (
                str_contains(strtolower($data['message']), 'upstream') ||
                str_contains(strtolower($data['message']), 'nimc') ||
                str_contains(strtolower($data['message']), 'unavailable') ||
                str_contains(strtolower($data['message']), 'service is currently')
            )) {
                \Log::warning('NIMC Service Unavailable', [
                    'firstName' => $request->firstName,
                    'lastName' => $request->lastName,
                    'user_id' => $user->id,
                    'response' => $data
                ]);
                
                return back()->with([
                    'status' => 'warning',
                    'message' => $errorMessage . ' This is a temporary issue with the verification service provider.'
                ]);
            }

            // Log API errors for debugging
            \Log::error('NIN Demo Verification API Error', [
                'firstName' => $request->firstName,
                'lastName' => $request->lastName,
                'user_id' => $user->id,
                'status_code' => $response->status(),
                'response' => $data
            ]);

            return back()->with([
                'status' => 'error',
                'message' => $errorMessage
            ]);

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            \Log::error('NIN Demo Verification Connection Error', [
                'firstName' => $request->firstName ?? 'N/A',
                'lastName' => $request->lastName ?? 'N/A',
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            
            return back()->with([
                'status' => 'error',
                'message' => 'Unable to connect to verification service. Please check your internet connection and try again.'
            ]);
        } catch (\Illuminate\Http\Client\RequestException $e) {
            \Log::error('NIN Demo Verification Request Error', [
                'firstName' => $request->firstName ?? 'N/A',
                'lastName' => $request->lastName ?? 'N/A',
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            
            return back()->with([
                'status' => 'error',
                'message' => 'Verification request failed. Please try again later.'
            ]);
        } catch (\Exception $e) {
            \Log::error('NIN Demo Verification System Error', [
                'firstName' => $request->firstName ?? 'N/A',
                'lastName' => $request->lastName ?? 'N/A',
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()->with([
                'status' => 'error',
                'message' => 'A system error occurred. Please contact support if this persists.'
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
                \Log::warning('NIN Demo Verification - No data in response', [
                    'user_id' => $user->id,
                    'response' => $apiResponse
                ]);
            }
            
            // Check for masked/suspended NIN data (indicated by ****)
            $isSuspended = false;
            $suspendedFields = [];
            
            // Check critical fields for masking
            $criticalFields = ['firstname', 'surname', 'nin', 'telephoneno'];
            foreach ($criticalFields as $field) {
                if (isset($ninData[$field]) && str_contains($ninData[$field], '****')) {
                    $isSuspended = true;
                    $suspendedFields[] = $field;
                }
            }
            
            // Log if suspended NIN is detected
            if ($isSuspended) {
                \Log::warning('NIN Demo Verification - Suspended NIN Detected', [
                    'user_id' => $user->id,
                    'suspended_fields' => $suspendedFields,
                    'nin_data' => $ninData
                ]);
            }
            
            $transactionRef = 'D1' . (time() % 1000000000) . '-' . mt_rand(100, 999);
            $performedBy = $user->first_name . ' ' . $user->last_name;

            // Determine status based on whether NIN is suspended
            $transactionStatus = $isSuspended ? 'suspended' : 'pending';
            $verificationStatus = $isSuspended ? 'suspended' : 'pending';

            $transaction = Transaction::create([
                'referenceId' => $transactionRef,
                'user_id' => $user->id,
                'amount' => $servicePrice,
                'service_type'    => 'NIN Demographic Verification',
                'service_description' => "NIN Demographic Verification - {$serviceField->field_name}",
                'type' => 'debit',
                'status' => "Approved",
            ]);

            // Deduct wallet balance
            $wallet->decrement('balance', $servicePrice);

            Verification::create([
                'user_id' => $user->id,
                'service_field_id' => $serviceField->id,
                'service_id' => $service->id,
                'transaction_id' => $transaction->id,
                'reference' => $transactionRef,
                'number_nin' => $ninData['nin'] ?? null,
                'idno' => $ninData['nin'] ?? null,
                'firstname' => $ninData['firstname'] ?? null,
                'middlename' => $ninData['middlename'] ?? null,
                'surname' => $ninData['surname'] ?? null,
                'birthdate' =>  $ninData['birthdate'] ?? null,
                'gender' => $ninData['gender'] ?? null,
                'telephoneno' => $ninData['telephoneno'] ?? null,
                'photo_path' => $ninData['photo'] ?? null,
                'signature_path' => $ninData['signature'] ?? null,
                'residence_state' => $ninData['residence_state'] ?? null,
                'residence_lga' => $ninData['residence_lga'] ?? null,
                'residence_town' => $ninData['residence_town'] ?? null,
                'residence_address' => $ninData['residence_AdressLine1'] ?? null,
                'self_origin_state' => $ninData['self_origin_state'] ?? null,
                'trackingId' => $ninData['trackingId'] ?? null,
                'performed_by'    => $performedBy,
                'submission_date' => Carbon::now(),
                'status' => 'pending',
            ]);

            DB::commit();

            // Flash normalized verification data for Blade
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

            // Prepare success message with warning if suspended
            $message = "NIN Demographic Verification successful. Reference: {$transactionRef}. Charged: NGN " . number_format($servicePrice, 2);
            
            if ($isSuspended) {
                $message .= " | WARNING: This NIN appears to be suspended or restricted. The verification service returned masked data (****). Please contact NIMC for assistance.";
            }

            return redirect()->route('user.nin.demo.index')->with([
                'status' => $isSuspended ? 'warning' : 'success',
                'message' => $message,
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

        $wallet->decrement('balance', $servicePrice);
        return $servicePrice;
    }

    /**
     * Helper to securely download NIN slip with atomic charge and rollback
     */
    private function downloadNinSlip($nin_no, $fieldCode, $pdfMethod)
    {
        $user = Auth::user();
        if (!$user) {
            if (request()->expectsJson() || request()->ajax()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }
            return redirect()->route('login');
        }

        // 1. Verify existence and ownership BEFORE charging
        $record = Verification::where(function ($q) use ($nin_no) {
            $q->where('number_nin', $nin_no)
              ->orWhere('nin', $nin_no)
              ->orWhere('id', $nin_no);
        })
        ->where(function ($q) use ($user) {
            if ($user->role !== 'admin') {
                $q->where('user_id', $user->id);
            }
        })
        ->latest()
        ->first();

        if (!$record) {
            if (request()->expectsJson() || request()->ajax()) {
                return response()->json(['message' => 'Verification record not found or you are not authorized.'], 404);
            }
            return back()->with('error', 'Verification record not found or you are not authorized.');
        }

        DB::beginTransaction();
        try {
            // 2. Charge wallet for slip
            $this->chargeForSlip($user, $fieldCode);

            // 3. Generate PDF
            $repObj = new NIN_PDF_Repository();
            $targetNin = $record->number_nin ?? $record->nin;
            $response = $repObj->$pdfMethod($targetNin);

            if ($response instanceof \Illuminate\Http\JsonResponse && $response->getStatusCode() >= 400) {
                DB::rollBack();
                return $response;
            }

            DB::commit();
            return $response;
        } catch (\Exception $e) {
            DB::rollBack();
            if (request()->expectsJson() || request()->ajax()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
            return back()->with('error', $e->getMessage());
        }
    }

    public function freeSlip($nin_no)
    {
        return $this->basicSlip($nin_no);
    }

    public function basicSlip($nin_no)
    {
        return $this->downloadNinSlip($nin_no, 'V101', 'basicPDF');
    }

    public function regularSlip($nin_no)
    {
        return $this->downloadNinSlip($nin_no, 'V102', 'regularPDF');
    }

    public function standardSlip($nin_no)
    {
        return $this->downloadNinSlip($nin_no, '611', 'standardPDF');
    }

    public function premiumSlip($nin_no)
    {
        return $this->downloadNinSlip($nin_no, '612', 'premiumPDF');
    }

    public function vninSlip($nin_no)
    {
        return $this->downloadNinSlip($nin_no, '616', 'vninPDF');
    }
}
