<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AgentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class NinPersonalisationController extends Controller
{
    /**
     * Display a listing of all NIN Personalisation requests.
     */
    public function index(Request $request)
    {
        $baseQuery = AgentService::where(function ($q) {
            $q->where('service_type', 'nin_personalization')
              ->orWhere('service_name', 'NIN Personalisation');
        });

        // Compute summary counts
        $total_request = (clone $baseQuery)->count();
        $pending       = (clone $baseQuery)->where('status', 'pending')->count();
        $processing    = (clone $baseQuery)->whereIn('status', ['processing', 'in_progress'])->count();
        $successful    = (clone $baseQuery)->whereIn('status', ['successful', 'resolved', 'approved', 'completed'])->count();
        $rejected      = (clone $baseQuery)->whereIn('status', ['rejected', 'failed', 'query', 'error'])->count();

        // Main filter query
        $query = (clone $baseQuery)->with(['user', 'transaction']);

        // Search Filter
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                  ->orWhere('tracking_id', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($u) use ($search) {
                      $u->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                  });
            });
        }

        // Status Filter
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Date Range Filters
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $submissions = $query->latest()->paginate(20)->withQueryString();

        return view('admin.nin.personalisation', compact(
            'submissions',
            'total_request',
            'pending',
            'processing',
            'successful',
            'rejected'
        ));
    }

    /**
     * Check status of a single submission via Arewa Smart API.
     */
    public function checkStatus($id)
    {
        $submission = AgentService::findOrFail($id);

        if (empty($submission->tracking_id)) {
            return back()->with([
                'status'  => 'error',
                'message' => 'Tracking ID is missing for this submission.',
            ]);
        }

        try {
            $apiKey     = config('services.arewa.token') ?? env('AREWA_API_TOKEN');
            $apiBaseUrl = config('services.arewa.base_url') ?? env('AREWA_BASE_URL', 'https://api.arewasmart.com.ng/api/v1');
            $apiUrl     = rtrim($apiBaseUrl, '/') . '/nin/personalisation';

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
                    'message' => "Status checked successfully for #{$submission->tracking_id}. Current status: " . ucfirst($submission->status),
                ]);
            }

            $errorMessage = $apiResponse['message'] ?? $apiResponse['error'] ?? 'Record not found or API error.';

            return back()->with([
                'status'  => 'error',
                'message' => 'Status check: ' . $errorMessage,
            ]);

        } catch (\Exception $e) {
            Log::error('Admin NIN Personalisation Status Check Error: ' . $e->getMessage());
            return back()->with([
                'status'  => 'error',
                'message' => 'Unable to check status: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Batch check status for pending/processing requests (up to 10 at a time).
     */
    public function batchCheck()
    {
        $submissions = AgentService::where(function ($q) {
            $q->where('service_type', 'nin_personalization')
              ->orWhere('service_name', 'NIN Personalisation');
        })
        ->whereIn('status', ['pending', 'processing', 'query'])
        ->whereNotNull('tracking_id')
        ->limit(10)
        ->get();

        if ($submissions->isEmpty()) {
            return back()->with([
                'status'  => 'info',
                'message' => 'No pending NIN Personalisation requests found to check.',
            ]);
        }

        $apiKey     = config('services.arewa.token') ?? env('AREWA_API_TOKEN');
        $apiBaseUrl = config('services.arewa.base_url') ?? env('AREWA_BASE_URL', 'https://api.arewasmart.com.ng/api/v1');
        $apiUrl     = rtrim($apiBaseUrl, '/') . '/nin/personalisation';

        $successCount = 0;
        $errorCount   = 0;

        foreach ($submissions as $submission) {
            try {
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
                    }

                    if (isset($data['file_url'])) {
                        $updateData['file_url'] = $data['file_url'];
                    } elseif (isset($apiResponse['file_url'])) {
                        $updateData['file_url'] = $apiResponse['file_url'];
                    }

                    if (!empty($updateData)) {
                        $submission->update($updateData);
                    }
                    $successCount++;
                } else {
                    $errorCount++;
                }
            } catch (\Exception $e) {
                Log::error('Batch check error for submission ' . $submission->id . ': ' . $e->getMessage());
                $errorCount++;
            }
        }

        return back()->with([
            'status'  => 'success',
            'message' => "Batch check completed: {$successCount} updated successfully, {$errorCount} unresolved.",
        ]);
    }

    /**
     * Manually update status and comments (Admin Override).
     */
    public function updateStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status'   => 'required|in:pending,processing,successful,query,resolved,rejected,remark,failed',
            'comment'  => 'nullable|string',
            'file_url' => 'nullable|string',
            'slip'     => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $submission = AgentService::findOrFail($id);

        $updateData = [
            'status'      => $validated['status'],
            'comment'     => $validated['comment'] ?? $submission->comment,
            'approved_by' => auth()->user()->name ?? 'Admin',
        ];

        // Handle direct file upload
        if ($request->hasFile('slip')) {
            $path = $request->file('slip')->store('nin_personalisation', 'public');
            $updateData['file_url'] = Storage::url($path);
        } elseif ($request->filled('file_url')) {
            $updateData['file_url'] = $validated['file_url'];
        }

        $submission->update($updateData);

        return back()->with([
            'status'  => 'success',
            'message' => "Request #{$submission->reference} status manually updated to " . ucfirst($validated['status']),
        ]);
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
