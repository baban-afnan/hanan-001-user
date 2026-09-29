<?php

namespace App\Http\Controllers\Agency;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\AgentService;
use App\Models\Services1 as Service;
use App\Models\ServiceField;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Http\Controllers\Controller;

class NinPersonalisationController extends Controller
{
    /**
     * Display the service form and submission history for NIN Personalisation.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $serviceKey = 'NIN Personalisation';

        // Query only this user's submissions
        $submissions = AgentService::with('transaction')
            ->where('user_id', $user->id)
            ->where('service_type', 'nin_personalization')
            ->when($request->filled('search'), fn($q) =>
                $q->where('tracking_id', 'like', "%{$request->search}%"))
            ->when($request->filled('status'), fn($q) =>
                $q->where('status', $request->status))
            ->orderByRaw("
                CASE
                    WHEN status = 'pending' THEN 1
                    WHEN status = 'processing' THEN 2
                    WHEN status = 'successful' THEN 3
                    WHEN status = 'query' THEN 4
                    ELSE 99
                END
            ")->orderByDesc('submission_date')
            ->paginate(10)
            ->withQueryString();

        // Load active service and its fields
        $service = Service::where('name', $serviceKey)
            ->where('is_active', true)
            ->with(['fields' => fn($q) => $q->where('is_active', true), 'prices'])
            ->first();

        $wallet = Wallet::firstOrCreate(
            ['user_id' => $user->id],
            ['balance' => 0.00]
        );

        $fields = $service?->fields ?? collect();
        $prices = $service?->prices ?? collect();
        $singleField = $fields->first();
        $servicePrice = $singleField ? ($singleField->getPriceForUserType($user->role) ?? $singleField->base_price ?? 1500.00) : 1500.00;

        return view('nin.personalisation', [
            'fieldname'     => $fields,
            'field'         => $singleField,
            'servicePrice'  => $servicePrice,
            'services'      => Service::where('is_active', true)->get(),
            'serviceName'   => $serviceKey,
            'submissions'   => $submissions,
            'servicePrices' => $prices,
            'wallet'        => $wallet,
        ]);
    }

    /**
     * Store submission for NIN Personalisation using Arewa Smart API.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $serviceKey = 'NIN Personalisation';

        // 1. Validation - only tracking_id is required
        $rules = [
            'tracking_id' => 'required|string|max:50',
        ];

        $validated = $request->validate($rules);

        // 2. Fetch Single Service Field and Price automatically
        $service = Service::where('name', $serviceKey)
            ->where('is_active', true)
            ->first();

        $serviceField = null;
        if ($service) {
            $serviceField = ServiceField::with(['service', 'prices'])
                ->where('service_id', $service->id)
                ->where('is_active', true)
                ->first();
        }

        if (!$serviceField) {
            $serviceField = ServiceField::with(['service', 'prices'])
                ->where('field_code', '005')
                ->first();
        }

        if (!$serviceField) {
            return back()->with([
                'status'  => 'error',
                'message' => 'NIN Personalisation service is currently unavailable.'
            ])->withInput();
        }

        $serviceName = $serviceField->service->name ?? $serviceKey;
        $fieldName = $serviceField->field_name ?? 'NIN Personalisation';
        $servicePrice = $serviceField->getPriceForUserType($user->role) ?? $serviceField->base_price ?? 1500.00;

        if ($servicePrice === null) {
            return back()->with([
                'status'  => 'error',
                'message' => 'Service price not configured for your account type.'
            ])->withInput();
        }

        DB::beginTransaction();

        try {
            // 3. Lock Wallet and Check Balance
            $wallet = Wallet::where('user_id', $user->id)->lockForUpdate()->first();
            if (!$wallet) {
                $wallet = Wallet::create([
                    'user_id' => $user->id,
                    'balance' => 0.00,
                ]);
            }

            // 4. Check Balance
            if ($wallet->balance < $servicePrice) {
                throw new \Exception('Insufficient balance. You need NGN ' . number_format($servicePrice - $wallet->balance, 2) . ' more.');
            }

            $reference = 'NINP' . date('is') . strtoupper(substr(uniqid(mt_rand(), true), -5));
            $performedBy = trim($user->first_name . ' ' . ($user->last_name ?? $user->surname));
            $description = "NIN Personalisation Request - " . $validated['tracking_id'];

            // 5. Create Transaction Record
            $transaction = Transaction::create([
                'referenceId'         => $reference,
                'user_id'             => $user->id,
                'amount'              => $servicePrice,
                'payer_name'          => $performedBy,
                'payer_email'         => $user->email,
                'payer_phone'         => $user->phone ?? null,
                'service_type'        => 'NIN Personalisation',
                'service_description' => $description,
                'type'                => 'debit',
                'status'              => 'Approved',
            ]);

            // 6. Create AgentService Record
            $agentService = AgentService::create([
                'reference'          => $reference,
                'user_id'            => $user->id,
                'service_id'         => $serviceField->service_id,
                'service_field_id'   => $serviceField->id,
                'field_code'         => $serviceField->field_code ?? '005',
                'service_name'       => $serviceName,
                'service_field_name' => $fieldName,
                'field_name'         => $fieldName,
                'tracking_id'        => $validated['tracking_id'],
                'description'        => $description,
                'amount'             => $servicePrice,
                'performed_by'       => $performedBy,
                'transaction_id'     => $transaction->id,
                'submission_date'    => now(),
                'status'             => 'pending',
                'service_type'       => 'nin_personalization', // Admin expects this specific string
            ]);

            // 7. Deduct Wallet Balance
            $wallet->decrement('balance', $servicePrice);

            // 8. Call Arewa Smart NIN Personalisation API
            $apiKey = config('services.arewa.token') ?? env('AREWA_API_TOKEN');
            $apiBaseUrl = config('services.arewa.base_url') ?? env('AREWA_BASE_URL', 'https://api.arewasmart.com.ng/api/v1');
            $apiUrl = rtrim($apiBaseUrl, '/') . '/nin/personalisation';

            $payload = [
                'field_code'  => $serviceField->field_code ?? '005',
                'tracking_id' => $validated['tracking_id'],
                'description' => $description,
            ];

            $response = Http::withToken($apiKey)
                ->acceptJson()
                ->withOptions(['verify' => (bool) config('services.validator.verify_ssl', false)])
                ->post($apiUrl, $payload);

            $decodedData = $response->json();

            // 9. Handle API Failure (Rollback)
            if (!$response->successful() || (isset($decodedData['success']) && $decodedData['success'] === false) || (isset($decodedData['status']) && in_array(strtolower($decodedData['status']), ['error', 'failed']))) {
                Log::error('NIN Personalisation API Failure', [
                    'reference' => $reference,
                    'response'  => $decodedData,
                    'status'    => $response->status(),
                ]);
                $errorMsg = $decodedData['message'] ?? $decodedData['error'] ?? 'Could not complete API request.';
                throw new \Exception('Transaction failed: ' . $errorMsg);
            }

            // 10. Update records with API Result and Commit
            $apiReference = $decodedData['data']['reference'] ?? $decodedData['reference'] ?? $reference;
            $apiStatus = $decodedData['data']['status'] ?? $decodedData['status'] ?? 'pending';
            $apiComment = $decodedData['data']['comment'] ?? $decodedData['comment'] ?? $decodedData['message'] ?? null;
            $fileUrl = $decodedData['data']['file_url'] ?? $decodedData['file_url'] ?? null;

            $updateData = [
                'reference' => $apiReference,
                'status'    => $this->normalizeStatus($apiStatus),
            ];

            if ($apiComment) {
                $updateData['comment'] = $apiComment;
            }
            if ($fileUrl) {
                $updateData['file_url'] = $fileUrl;
            }

            $agentService->update($updateData);

            if ($apiReference !== $reference) {
                $transaction->update(['referenceId' => $apiReference]);
            }

            DB::commit();

            $targetRoute = \Illuminate\Support\Facades\Route::has('user.nin.personalisation.index')
                ? 'user.nin.personalisation.index'
                : 'nin-personalisation.index';

            return redirect()->route($targetRoute)->with([
                'status'  => 'success',
                'message' => "NIN Personalisation request submitted successfully. Ref: {$apiReference}. Charged: ₦" . number_format($servicePrice, 2),
            ]);

        } catch (\Exception $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            Log::error('NIN Personalisation Store Exception', [
                'user_id' => $user->id,
                'error'   => $e->getMessage()
            ]);

            return back()->with([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ])->withInput();
        }
    }

    /**
     * Check the status of a NIN Personalisation submission.
     */
    public function checkStatus($id)
    {
        $submission = AgentService::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        if (empty($submission->tracking_id)) {
            return back()->with([
                'status'  => 'error',
                'message' => 'Tracking ID is missing for this submission.',
            ]);
        }

        try {
            $apiKey = config('services.arewa.token') ?? env('AREWA_API_TOKEN');
            $apiBaseUrl = config('services.arewa.base_url') ?? env('AREWA_BASE_URL', 'https://api.arewasmart.com.ng/api/v1');
            $apiUrl = rtrim($apiBaseUrl, '/') . '/nin/personalisation';

            $response = Http::withToken($apiKey)
                ->acceptJson()
                ->withOptions(['verify' => (bool) config('services.validator.verify_ssl', false)])
                ->get($apiUrl, [
                    'tracking_id' => $submission->tracking_id,
                ]);

            $apiResponse = $response->json();

            if ($response->successful() && ($apiResponse['success'] ?? false)) {
                $data = $apiResponse['data'] ?? $apiResponse;
                
                $updateData = [];

                if (isset($data['status'])) {
                    $updateData['status'] = $this->normalizeStatus($data['status']);
                } elseif (isset($apiResponse['status'])) {
                    $updateData['status'] = $this->normalizeStatus($apiResponse['status']);
                }
                
                if (isset($data['comment'])) {
                    $updateData['comment'] = $data['comment'];
                } elseif (isset($apiResponse['comment'])) {
                    $updateData['comment'] = $apiResponse['comment'];
                } elseif (isset($apiResponse['message'])) {
                    $updateData['comment'] = $apiResponse['message'];
                } elseif (isset($data['reason'])) {
                    $updateData['comment'] = $data['reason'];
                }

                if (isset($data['file_url'])) {
                    $updateData['file_url'] = $data['file_url'];
                } elseif (isset($apiResponse['file_url'])) {
                    $updateData['file_url'] = $apiResponse['file_url'];
                }

                if (!empty($updateData)) {
                    $submission->update($updateData);
                    $submission->refresh();
                }

                return back()->with([
                    'status'  => 'success',
                    'message' => 'Status checked successfully. Current status: ' . ucfirst($submission->status),
                ]);
            }

            $errorMessage = $apiResponse['message'] ?? $apiResponse['error'] ?? 'Record not found or API error.';

            return back()->with([
                'status'  => 'error',
                'message' => 'Status check: ' . $errorMessage,
            ]);

        } catch (\Exception $e) {
            Log::error('NIN Personalisation Status Check Error: ' . $e->getMessage());
            return back()->with([
                'status'  => 'error',
                'message' => 'Unable to check status: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Normalize status string to standard format.
     */
    private function normalizeStatus($status): string
    {
        $s = strtolower(trim((string) $status));
        return match ($s) {
            'successful', 'success', 'resolved', 'approved', 'completed' => 'successful',
            'processing', 'in_progress', 'in-progress' => 'processing',
            'failed', 'rejected', 'error', 'declined' => 'failed',
            'query' => 'query',
            'remark' => 'remark',
            default => 'pending',
        };
    }
}
