<?php

namespace App\Http\Controllers\Verification;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\Verification;
use App\Models\Transaction;
use App\Helpers\ServiceManager;
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
        $basicSlipPrice = 0;
        $regularSlipPrice = 0;
        $standardSlipPrice = 0;
        $premiumSlipPrice = 0;
        $vninSlipPrice = 0;

        if ($service) {
            $verificationField = $service->fields()->where('field_code', '610')->first();
            $basicSlipField = $service->fields()->where('field_code', 'V101')->first();
            $regularSlipField = $service->fields()->where('field_code', 'V102')->first();
            $standardSlipField = $service->fields()->where('field_code', '611')->first();
            $premiumSlipField = $service->fields()->where('field_code', '612')->first();
            $vninSlipField = $service->fields()->where('field_code', '616')->first();

            $verificationPrice = $verificationField ? $verificationField->getPriceForUserType($user->role) : 0;
            $basicSlipPrice = $basicSlipField ? $basicSlipField->getPriceForUserType($user->role) : 0;
            $regularSlipPrice = $regularSlipField ? $regularSlipField->getPriceForUserType($user->role) : 0;
            $standardSlipPrice = $standardSlipField ? $standardSlipField->getPriceForUserType($user->role) : 0;
            $premiumSlipPrice = $premiumSlipField ? $premiumSlipField->getPriceForUserType($user->role) : 0;
            $vninSlipPrice = $vninSlipField ? $vninSlipField->getPriceForUserType($user->role) : 0;
        }

        $wallet = Wallet::where('user_id', $user->id)->first();

        return view('verification.nin-verification', [
            'wallet' => $wallet,
            'verificationPrice' => $verificationPrice,
            'basicSlipPrice' => $basicSlipPrice,
            'regularSlipPrice' => $regularSlipPrice,
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
            $apiKey = config('services.arewa.token') ?? env('AREWA_API_TOKEN');
            $apiBaseUrl = config('services.arewa.base_url') ?? env('AREWA_BASE_URL', 'https://api.arewasmart.com.ng/api/v1');
            $apiUrl = rtrim($apiBaseUrl, '/') . '/nin/verify';

            $response = Http::withToken($apiKey)
                ->acceptJson()
                ->withOptions([
                    'verify' => (bool) config('services.validator.verify_ssl', false),
                ])
                ->post($apiUrl, [
                    'nin' => $request->number_nin,
                ]);

            // Log the raw response for debugging
            Log::info('NIN Verification Response', [
                'status' => $response->status(),
                'response' => $response->json()
            ]);

            $decodedData = $response->json();

            // 1. Verification fails if HTTP status code is not 200
            if ($response->status() !== 200) {
                return back()->with([
                    'status' => 'error',
                    'message' => 'API Error: ' . ($decodedData['message'] ?? ('Verification failed with status code ' . $response->status()))
                ]);
            }

            // 2. Check if status indicates success
            $status = $decodedData['status'] ?? 'UNKNOWN';
            $isSuccessStatus = ($status === 'success' || $status === true || $status === 200 || $status === '200');

            // 3. Ensure verification information/data is returned
            $apiData = [];
            if (!empty($decodedData['data']) && is_array($decodedData['data'])) {
                $apiData = isset($decodedData['data']['data']) && is_array($decodedData['data']['data'])
                    ? $decodedData['data']['data']
                    : $decodedData['data'];
            } elseif (!empty($decodedData['api_response']['data']['data']) && is_array($decodedData['api_response']['data']['data'])) {
                $apiData = $decodedData['api_response']['data']['data'];
            } elseif (!empty($decodedData['api_response']['data']) && is_array($decodedData['api_response']['data'])) {
                $apiData = $decodedData['api_response']['data'];
            }

            if (is_array($apiData) && isset($apiData[0]) && is_array($apiData[0])) {
                $apiData = $apiData[0];
            }

            $hasVerificationData = !empty($apiData) && is_array($apiData);

            if (!$isSuccessStatus || !$hasVerificationData) {
                return back()->with([
                    'status' => 'error',
                    'message' => $decodedData['message'] ?? 'Verification failed: No verification data returned from provider.'
                ]);
            }

            // Check if NIN is suspended (contains **** in critical fields)
            $isSuspended = false;
            $suspendedFields = ['firstname', 'surname', 'nin'];
            
            foreach ($suspendedFields as $field) {
                $value = $apiData[$field] ?? ($apiData[str_replace('_', '', strtolower($field))] ?? '');
                if (is_string($value) && (strpos($value, '****') !== false || strpos($value, '*****') !== false || $value === '*')) {
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
            
            // Successful (HTTP 200 + Success Status + Valid Verification Data) -> Charge + Create Transaction + Create Verification
            return $this->processSuccessTransaction(
                $wallet,
                $servicePrice,
                $user,
                $serviceField,
                $service,
                $decodedData,
                $apiData,
                $request->number_nin
            );

        } catch (\Exception $e) {
             // System/Network Error -> No Charge + Transaction Log if possible (optional, but good for tracking)
            return back()->with([
                'status' => 'error',
                'message' => 'System Error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Process successful transaction (Charge + Verification Record)
     */
    private function processSuccessTransaction($wallet, $servicePrice, $user, $serviceField, $service, $decodedData, $apiData, $inputNin)
    {
        DB::beginTransaction();

        try {
            $transactionRef = 'Ver-' . (time() % 1000000000) . '-' . mt_rand(100, 999);
            $performedBy = $user->first_name . ' ' . $user->last_name;

            // Normalize core fields
            $nin = $apiData['nin'] ?? ($apiData['number_nin'] ?? $inputNin);
            $firstname = $apiData['firstname'] ?? ($apiData['firstName'] ?? ($apiData['first_name'] ?? ''));
            $middlename = $apiData['middlename'] ?? ($apiData['middleName'] ?? ($apiData['middle_name'] ?? ''));
            $surname = $apiData['surname'] ?? ($apiData['lastName'] ?? ($apiData['last_name'] ?? ''));
            $birthdate = $apiData['birthdate'] ?? ($apiData['birthDate'] ?? ($apiData['dob'] ?? ($apiData['birthday'] ?? '')));

            $rawGender = strtolower(trim($apiData['gender'] ?? ''));
            if ($rawGender === 'm' || $rawGender === 'male') {
                $gender = 'Male';
            } elseif ($rawGender === 'f' || $rawGender === 'female') {
                $gender = 'Female';
            } else {
                $gender = $apiData['gender'] ?? '';
            }

            $telephoneno = $apiData['telephoneno'] ?? ($apiData['telephoneNo'] ?? ($apiData['phone'] ?? ($apiData['phoneNumber'] ?? '')));
            
            // Clean photo and signature base64 prefix
            $rawPhoto = $apiData['photo'] ?? ($apiData['photo_path'] ?? '');
            $photo = preg_replace('/^data:image\/[a-zA-Z]+;base64,/', '', $rawPhoto);

            $rawSignature = $apiData['signature'] ?? ($apiData['signature_path'] ?? '');
            $signature = preg_replace('/^data:image\/[a-zA-Z]+;base64,/', '', $rawSignature);

            $trackingId = $apiData['trackingId'] ?? ($apiData['tracking_id'] ?? '');

            $residenceAddress = $apiData['residence_AdressLine1'] ?? ($apiData['residence_address'] ?? ($apiData['address'] ?? ''));
            $residenceState = $apiData['residence_state'] ?? ($apiData['residenceState'] ?? '');
            $residenceLga = $apiData['residence_lga'] ?? ($apiData['residenceLga'] ?? '');
            $residenceTown = $apiData['residence_Town'] ?? ($apiData['residence_town'] ?? ($apiData['residenceTown'] ?? ''));

            $birthState = $apiData['birthstate'] ?? ($apiData['birthState'] ?? '');
            $birthLga = $apiData['birthlga'] ?? ($apiData['birthLga'] ?? '');
            $birthCountry = $apiData['birthcountry'] ?? ($apiData['birthCountry'] ?? '');

            $maritalStatus = $apiData['maritalstatus'] ?? ($apiData['maritalStatus'] ?? '');
            $email = $apiData['email'] ?? '';
            $religion = $apiData['religion'] ?? '';
            $employmentStatus = $apiData['emplymentstatus'] ?? ($apiData['employmentstatus'] ?? ($apiData['employmentStatus'] ?? ''));
            $educationalLevel = $apiData['educationallevel'] ?? ($apiData['educationalLevel'] ?? '');
            $profession = $apiData['profession'] ?? '';
            $height = $apiData['heigth'] ?? ($apiData['height'] ?? '');
            $title = $apiData['title'] ?? '';
            $centralID = $apiData['centralID'] ?? ($apiData['userid'] ?? '');

            // Next of Kin
            $nokFirstname = $apiData['nok_firstname'] ?? '';
            $nokMiddlename = $apiData['nok_middlename'] ?? '';
            $nokSurname = $apiData['nok_surname'] ?? '';
            $nokAddress1 = $apiData['nok_address1'] ?? '';
            $nokAddress2 = $apiData['nok_address2'] ?? '';
            $nokLga = $apiData['nok_lga'] ?? '';
            $nokState = $apiData['nok_state'] ?? '';
            $nokTown = $apiData['nok_town'] ?? '';
            $nokPostalcode = $apiData['nok_postalcode'] ?? '';

            // Self Origin
            $selfOriginState = $apiData['self_origin_state'] ?? '';
            $selfOriginLga = $apiData['self_origin_lga'] ?? '';
            $selfOriginPlace = $apiData['self_origin_place'] ?? '';

            $transaction = Transaction::create([
                'referenceId' => $transactionRef,
                'user_id' => $user->id,
                'amount' => $servicePrice,
                'service_type' => 'NIN Verification',
                'service_description' => "NIN Verification - {$serviceField->field_name}",
                'type' => 'debit',
                'status' => 'Approved',
            ]);

            // Deduct wallet balance
            $wallet->decrement('balance', $servicePrice);

            Verification::create([
                'reference' => $transactionRef,
                'user_id' => $user->id,
                'service_field_id' => $serviceField->id,
                'service_id' => $service->id,
                'transaction_id' => $transaction->id,
                'field_code' => $serviceField->field_code ?? '610',
                'field_name' => $serviceField->field_name ?? 'Verify NIN',
                'service_name' => $service->service_name ?? 'Verification',
                'service_type' => $service->service_type ?? 'Verification',
                'description' => "NIN Verification - {$serviceField->field_name}",
                'amount' => $servicePrice,
                'status' => 'successful',

                // Personal Information
                'firstname' => $firstname,
                'middlename' => $middlename,
                'surname' => $surname,
                'gender' => $gender,
                'birthdate' => $birthdate,
                'birthstate' => $birthState,
                'birthlga' => $birthLga,
                'birthcountry' => $birthCountry,
                'maritalstatus' => $maritalStatus,
                'email' => $email,
                'telephoneno' => $telephoneno,

                // Residence Information
                'residence_address' => $residenceAddress,
                'residence_state' => $residenceState,
                'residence_lga' => $residenceLga,
                'residence_town' => $residenceTown,

                // Additional Information
                'religion' => $religion,
                'employmentstatus' => $employmentStatus,
                'educationallevel' => $educationalLevel,
                'profession' => $profession,
                'height' => $height,
                'title' => $title,

                // Identifiers
                'nin' => $nin,
                'number_nin' => $nin,
                'idno' => $nin,
                'vnin' => $apiData['vnin'] ?? null,
                'userid' => $centralID,
                'photo_path' => $photo,
                'signature_path' => $signature,
                'trackingId' => $trackingId,

                // Next of Kin
                'nok_firstname' => $nokFirstname,
                'nok_middlename' => $nokMiddlename,
                'nok_surname' => $nokSurname,
                'nok_address1' => $nokAddress1,
                'nok_address2' => $nokAddress2,
                'nok_lga' => $nokLga,
                'nok_state' => $nokState,
                'nok_town' => $nokTown,
                'nok_postalcode' => $nokPostalcode,

                // Self Origin
                'self_origin_state' => $selfOriginState,
                'self_origin_lga' => $selfOriginLga,
                'self_origin_place' => $selfOriginPlace,

                'performed_by'    => $performedBy,
                'submission_date' => Carbon::now(),
                'response_data' => $decodedData,
            ]);

            DB::commit();

            // Flash normalized verification data for Blade (supporting both camelCase and lowercase)
            session()->flash('verification', [
                'status' => true,
                'message' => 'NIN Verification Successful',
                'data' => [
                    'nin' => $nin,
                    'number_nin' => $nin,
                    'firstName' => $firstname,
                    'firstname' => $firstname,
                    'surname' => $surname,
                    'lastName' => $surname,
                    'middleName' => $middlename,
                    'middlename' => $middlename,
                    'birthDate' => $birthdate,
                    'birthdate' => $birthdate,
                    'gender' => $gender,
                    'telephoneNo' => $telephoneno,
                    'telephoneno' => $telephoneno,
                    'photo' => $photo,
                    'residence_address' => $residenceAddress,
                    'residence_state' => $residenceState,
                    'residence_lga' => $residenceLga,
                    'trackingId' => $trackingId,
                ]
            ]);

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

    /**
     * Download NIN slips
     */
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

    /**
     * Update verification status (for route nin.verification.status)
     */
    public function updateStatus(Request $request, $id)
    {
        $verification = Verification::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $verification->update([
            'status' => $request->input('status', $verification->status),
        ]);

        return response()->json(['success' => true, 'message' => 'Status updated successfully.']);
    }
}
