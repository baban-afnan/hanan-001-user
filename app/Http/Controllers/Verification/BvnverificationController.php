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
            $apiKey = config('services.arewa.token') ?? env('AREWA_API_TOKEN');
            $apiBaseUrl = config('services.arewa.base_url') ?? env('AREWA_BASE_URL', 'https://api.arewasmart.com.ng/api/v1');
            $apiUrl = rtrim($apiBaseUrl, '/') . '/bvn/verify';

            $response = Http::withoutVerifying()
                ->withToken($apiKey)
                ->acceptJson()
                ->post($apiUrl, [
                    'bvn' => $validated['bvn'],
                ]);

            // Log the raw response for debugging
            Log::info('BVN Verification Response', [
                'status' => $response->status(),
                'response' => $response->json()
            ]);

            $decodedData = $response->json();

            if (!$response->successful() || (isset($decodedData['status']) && ($decodedData['status'] === 'error' || $decodedData['status'] === false))) {
                return back()->with([
                    'status' => 'error',
                    'message' => 'API Error: ' . ($decodedData['message'] ?? 'Unknown error occurred.')
                ]);
            }

            // Arewa Smart API usually returns success in 'status' field
            $status = $decodedData['status'] ?? 'UNKNOWN';
            $isSuccess = ($status === 'success' || $status === true || $status === 200 || $status === '200');

            if ($isSuccess) {
                 // Successful -> Charge + Create Transaction + Create Verification
                 return $this->processSuccessTransaction(
                    $wallet,
                    $servicePrice,
                    $user,
                    $serviceField,
                    $service,
                    $decodedData,
                    $validated['bvn']
                );
            } else {
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
    private function processSuccessTransaction($wallet, $servicePrice, $user, $serviceField, $service, $bvnData, $inputBvn)
    {
        DB::beginTransaction();

        try {
            $transactionRef = 'Ver-' . (time() % 1000000000) . '-' . mt_rand(100, 999);
            $performedBy = $user->first_name . ' ' . $user->last_name;

            // Extract nested data payload if any
            $apiData = [];
            if (!empty($bvnData['data']) && is_array($bvnData['data'])) {
                $apiData = isset($bvnData['data']['data']) && is_array($bvnData['data']['data'])
                    ? $bvnData['data']['data']
                    : $bvnData['data'];
            } elseif (!empty($bvnData['api_response']['data']['data']) && is_array($bvnData['api_response']['data']['data'])) {
                $apiData = $bvnData['api_response']['data']['data'];
            } elseif (!empty($bvnData['api_response']['data']) && is_array($bvnData['api_response']['data'])) {
                $apiData = $bvnData['api_response']['data'];
            } elseif (is_array($bvnData)) {
                $apiData = $bvnData;
            }

            if (is_array($apiData) && isset($apiData[0]) && is_array($apiData[0])) {
                $apiData = $apiData[0];
            }

            // Normalization of fields
            $bvn = $apiData['bvn'] ?? ($apiData['idno'] ?? $inputBvn);
            $firstname = $apiData['firstName'] ?? ($apiData['first_name'] ?? ($apiData['firstname'] ?? ''));
            $middlename = $apiData['middleName'] ?? ($apiData['middle_name'] ?? ($apiData['middlename'] ?? ''));
            $surname = $apiData['lastName'] ?? ($apiData['last_name'] ?? ($apiData['surname'] ?? ''));
            $birthdate = $apiData['birthday'] ?? ($apiData['dob'] ?? ($apiData['birthDate'] ?? ($apiData['birthdate'] ?? '')));

            $rawGender = strtolower(trim($apiData['gender'] ?? ''));
            if ($rawGender === 'm' || $rawGender === 'male') {
                $gender = 'Male';
            } elseif ($rawGender === 'f' || $rawGender === 'female') {
                $gender = 'Female';
            } else {
                $gender = !empty($apiData['gender']) ? ucfirst($apiData['gender']) : '';
            }

            $maritalstatus = $apiData['maritalStatus'] ?? ($apiData['maritalstatus'] ?? '');
            $nationality = $apiData['nationality'] ?? 'Nigerian';
            $telephoneno = $apiData['phoneNumber'] ?? ($apiData['phone'] ?? ($apiData['telephoneno'] ?? ($apiData['phoneNumber1'] ?? '')));
            $email = $apiData['email'] ?? '';

            $rawPhoto = $apiData['photo'] ?? ($apiData['photo_path'] ?? ($apiData['image'] ?? ''));
            $photo = preg_replace('/^data:image\/[a-zA-Z0-9]+;base64,/', '', $rawPhoto);

            $enrollmentBank = $apiData['enrollmentBank'] ?? ($apiData['enrollment_bank'] ?? '');
            $enrollmentBranch = $apiData['enrollmentBranch'] ?? ($apiData['enrollment_branch'] ?? '');
            $registrationDate = $apiData['registrationDate'] ?? ($apiData['registration_date'] ?? null);

            $stateOfOrigin = $apiData['stateOfOrigin'] ?? ($apiData['self_origin_state'] ?? '');
            $lgaOfOrigin = $apiData['lgaOfOrigin'] ?? ($apiData['self_origin_lga'] ?? '');

            $stateOfResidence = $apiData['stateOfResidence'] ?? ($apiData['residence_state'] ?? '');
            $lgaOfResidence = $apiData['lgaOfResidence'] ?? ($apiData['residence_lga'] ?? '');
            $residentialAddress = $apiData['residentialAddress'] ?? ($apiData['address'] ?? ($apiData['residence_address'] ?? ''));

            $nin = $apiData['nin'] ?? ($apiData['number_nin'] ?? null);
            $title = $apiData['title'] ?? '';
            $trackingId = $apiData['trackingId'] ?? ($apiData['tracking_id'] ?? '');
            $levelOfAccount = $apiData['levelOfAccount'] ?? ($apiData['userid'] ?? '');

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
                    'bvn' => $bvn,
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

            // Populate all columns in verifications table
            Verification::create([
                'reference'          => $transactionRef,
                'user_id'            => $user->id,
                'service_field_id'   => $serviceField->id,
                'service_id'         => $service->id,
                'transaction_id'     => $transaction->id,
                'field_code'         => $serviceField->field_code ?? '600',
                'field_name'         => $serviceField->field_name ?? 'BVN Verification',
                'service_name'       => $service->name ?? ($service->service_name ?? 'Verification'),
                'service_type'       => $service->service_type ?? 'BVN Verification',
                'description'        => "BVN Verification - {$serviceField->field_name}",
                'amount'             => $servicePrice,
                'status'             => 'successful',
                'submission_date'    => Carbon::now(),

                // Identifiers (saving BVN in idno)
                'idno'               => $bvn,
                'type'               => 'BVN',
                'nin'                => $nin,
                'number_nin'         => $nin,
                'vnin'               => $apiData['vnin'] ?? null,
                'trackingId'         => $trackingId,
                'userid'             => $levelOfAccount,

                // Personal details
                'firstname'          => $firstname,
                'middlename'         => $middlename,
                'surname'            => $surname,
                'gender'             => $gender,
                'birthdate'          => $birthdate,
                'birthcountry'       => $nationality,
                'birthstate'         => $stateOfOrigin,
                'birthlga'           => $lgaOfOrigin,
                'maritalstatus'      => $maritalstatus,
                'email'              => $email,
                'telephoneno'        => $telephoneno,
                'title'              => $title,

                // Residence & Contact
                'residence_address'  => $residentialAddress,
                'residence_state'    => $stateOfResidence,
                'residence_lga'      => $lgaOfResidence,
                'address'            => $residentialAddress,
                'state'              => $stateOfResidence,
                'lga'                => $lgaOfResidence,

                // Origin
                'self_origin_state'  => $stateOfOrigin,
                'self_origin_lga'    => $lgaOfOrigin,

                // Enrollment Details
                'enrollment_bank'    => $enrollmentBank,
                'enrollment_branch'  => $enrollmentBranch,
                'registration_date'  => $registrationDate,

                // Photo
                'photo_path'         => $photo,

                // Audit & Raw Data
                'performed_by'       => $performedBy,
                'response_data'      => $apiData,
            ]);

            DB::commit();

            // Sync all normalized keys into session data
            $apiData['bvn'] = $bvn;
            $apiData['idno'] = $bvn;
            $apiData['photo'] = $photo;
            $apiData['firstName'] = $firstname;
            $apiData['lastName'] = $surname;
            $apiData['middleName'] = $middlename;
            $apiData['birthday'] = $birthdate;
            $apiData['gender'] = $gender;
            $apiData['maritalStatus'] = $maritalstatus;
            $apiData['nationality'] = $nationality;
            $apiData['phoneNumber'] = $telephoneno;
            $apiData['stateOfOrigin'] = $stateOfOrigin;
            $apiData['lgaOfOrigin'] = $lgaOfOrigin;
            $apiData['stateOfResidence'] = $stateOfResidence;
            $apiData['lgaOfResidence'] = $lgaOfResidence;
            $apiData['residentialAddress'] = $residentialAddress;
            $apiData['enrollmentBank'] = $enrollmentBank;
            $apiData['enrollmentBranch'] = $enrollmentBranch;
            $apiData['registrationDate'] = $registrationDate;
            $apiData['levelOfAccount'] = $levelOfAccount;

            // Flash normalized verification data for Blade
            session()->flash('verification', [
                'status' => 'success',
                'data'   => $apiData,
            ]);

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
        $performedBy = $user->first_name . ' ' . $user->last_name;

        Transaction::create([
            'referenceId' => $transactionRef,
            'user_id' => $user->id,
            'amount' => $servicePrice,
            'service_type' => 'Slip Download',
            'service_description' => "Slip Download: {$serviceField->field_name}",
            'type' => 'debit',
            'status' => 'Approved',
            'performed_by' => $performedBy,
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
        $bvn_no = trim($bvn_no);

        $veridiedRecord = Verification::where('user_id', $user->id)
            ->where(function($q) use ($bvn_no) {
                $q->where('idno', $bvn_no)
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
        $bvn_no = trim($bvn_no);

        $veridiedRecord = Verification::where('user_id', $user->id)
            ->where(function($q) use ($bvn_no) {
                $q->where('idno', $bvn_no)
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
        $bvn_no = trim($bvn_no);

        $veridiedRecord = Verification::where('user_id', $user->id)
            ->where(function($q) use ($bvn_no) {
                $q->where('idno', $bvn_no)
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
            $targetBvn = $veridiedRecord->idno ?? $bvn_no;
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
