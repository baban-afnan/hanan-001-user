<x-app-layout>
    <title>Biometric id - {{ $title ?? 'NIN Personalisation' }}</title>

    <div class="page-body">
        <div class="container-fluid">
            <div class="page-title mb-3">
                <div class="row align-items-center">
                    <div class="col-sm-6 col-12">
                        <h3 class="fw-bold text-dark">NIN Personalisation</h3>
                        <p class="text-muted small mb-0">Submit and track your NIN personalisation requests seamlessly.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="container-fluid mt-3">
            <div class="row">
                {{-- Request Form Column --}}
                <div class="col-xl-5 mb-4">
                    <div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100">
                        <div class="card-header bg-white py-3 border-bottom-0">
                            <h5 class="mb-0 fw-bold text-primary">
                                <i class="ti ti-id-badge-2 me-2"></i>New Personalisation Request
                            </h5>
                        </div>

                        <div class="card-body p-4">
                            {{-- Alerts --}}
                            @php
                                $alertType = null;
                                $alertMessage = null;
                                if (session('status')) {
                                    $alertType = session('status') === 'success' ? 'success' : 'danger';
                                    $alertMessage = session('message');
                                } elseif (session('success')) {
                                    $alertType = 'success';
                                    $alertMessage = session('success');
                                } elseif (session('error')) {
                                    $alertType = 'danger';
                                    $alertMessage = session('error');
                                } elseif (session('message')) {
                                    $alertType = 'info';
                                    $alertMessage = session('message');
                                }
                            @endphp

                            @if ($alertMessage)
                                <div class="alert alert-{{ $alertType }} alert-dismissible fade show border-0 shadow-sm mb-4">
                                    <i class="ti {{ $alertType === 'success' ? 'ti-check' : ($alertType === 'danger' ? 'ti-alert-triangle' : 'ti-info-circle') }} me-2"></i>
                                    {{ $alertMessage }}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            @endif

                            @if ($errors->any())
                                <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4">
                                    <ul class="mb-0 small">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            @endif

                            <!-- Non-Refundable Notice Banner -->
                            <div class="alert alert-light border border-warning-subtle text-dark small mb-4 rounded-3 d-flex align-items-center">
                                <i class="ti ti-alert-circle-filled text-warning fs-3 me-2 flex-shrink-0"></i>
                                <div>
                                    <strong class="text-warning">Important Notice:</strong> NIN Personalisation requests are strictly <strong>NON-REFUNDABLE</strong> once submitted. Double-check your Tracking ID before continuing.
                                </div>
                            </div>

                            {{-- Submission Form --}}
                            <form method="POST" action="{{ route('user.nin.personalisation.store') }}" class="row g-4" id="personalisationForm" onsubmit="return handleFormSubmit(event)">
                                @csrf

                                <!-- Tracking ID Input -->
                                <div class="col-12">
                                    <label class="form-label fw-bold small text-muted text-uppercase">Tracking ID <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-light-subtle"><i class="ti ti-scan text-muted"></i></span>
                                        <input class="form-control border-light-subtle shadow-sm"
                                               name="tracking_id"
                                               type="text"
                                               required
                                               placeholder="e.g. WN0987654321XYZ"
                                               value="{{ old('tracking_id') }}">
                                    </div>
                                    <div class="form-text text-muted small mt-1">
                                        <i class="ti ti-info-circle me-1"></i>Please ensure tracking ID is entered accurately as given on your slip.
                                    </div>
                                </div>

                                <!-- Dynamic Pricing & Wallet Balance Card -->
                                <div class="col-12">
                                    <div class="card bg-primary bg-opacity-10 border-0 rounded-4 mt-2">
                                        <div class="card-body py-3">
                                            <div class="row align-items-center">
                                                <div class="col-6">
                                                    <small class="text-primary fw-bold text-uppercase small">Service Fee</small>
                                                    <h3 class="fw-bold text-primary mb-0" id="field-price">₦{{ number_format($servicePrice ?? 1500, 2) }}</h3>
                                                </div>
                                                <div class="col-6 text-end border-start border-primary border-opacity-25">
                                                    <small class="text-muted fw-bold text-uppercase small">Wallet Balance</small>
                                                    <h5 class="fw-bold text-success mb-0 d-flex align-items-center justify-content-end gap-1">
                                                        <i class="ti ti-wallet"></i>
                                                        ₦{{ number_format($wallet->balance ?? 0, 2) }}
                                                    </h5>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Submit Button -->
                                <div class="col-12 d-grid mt-3">
                                    <button type="submit" class="btn btn-primary btn-lg rounded-pill shadow hover-up" id="submitBtn">
                                        <span id="submitText">Submit Personalisation</span>
                                        <i class="ti ti-send ms-2"></i>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                {{-- Request History Column --}}
                <div class="col-xl-7">
                    <div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100">
                        <div class="card-header bg-white py-3 border-bottom-0 d-flex align-items-center justify-content-between">
                            <h5 class="fw-bold mb-0 text-dark">
                                <i class="ti ti-history me-2 text-primary"></i> Request History
                            </h5>
                            <span class="badge bg-light text-primary border px-3 py-2 rounded-pill">
                                {{ $submissions->total() }} Submissions
                            </span>
                        </div>

                        <div class="card-body p-0">
                            <!-- Filter Bar -->
                            <div class="px-3 pb-3">
                                <form class="row g-2 bg-light p-2 rounded-3 border border-light-subtle" method="GET" action="{{ route('user.nin.personalisation.index') }}">
                                    <div class="col-md-5">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text border-0 bg-white"><i class="ti ti-search text-muted"></i></span>
                                            <input class="form-control border-0 shadow-none bg-white"
                                                   name="search"
                                                   type="text"
                                                   placeholder="Search Tracking ID..."
                                                   value="{{ request('search') }}">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <select class="form-select form-select-sm border-0 shadow-none bg-white" name="status">
                                            <option value="">All Statuses</option>
                                            @foreach(['pending','processing','successful','query','resolved','rejected','remark','failed'] as $status)
                                                <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>
                                                    {{ ucfirst($status) }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <button class="btn btn-primary btn-sm w-100 rounded-pill shadow-sm" type="submit">
                                            Apply Filter
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <!-- Table -->
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th class="ps-4 text-muted small fw-bold text-uppercase">Tracking ID / Ref</th>
                                            <th class="text-muted small fw-bold text-uppercase">Date</th>
                                            <th class="text-muted small fw-bold text-uppercase">Status</th>
                                            <th class="text-end pe-4 text-muted small fw-bold text-uppercase">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($submissions as $submission)
                                            <tr>
                                                <td class="ps-4">
                                                    <span class="text-dark fw-bold d-block font-monospace">{{ $submission->tracking_id }}</span>
                                                    <small class="text-primary fs-11">{{ $submission->reference }}</small>
                                                </td>
                                                <td>
                                                    <small class="text-muted fs-12">
                                                        <i class="ti ti-calendar me-1"></i>{{ $submission->created_at ? $submission->created_at->format('M d, Y H:i') : 'N/A' }}
                                                    </small>
                                                </td>
                                                <td>
                                                    @php
                                                        $st = strtolower($submission->status);
                                                        $badgeClass = match($st) {
                                                            'successful', 'success', 'resolved', 'approved', 'completed' => 'bg-success-subtle text-success border border-success-subtle',
                                                            'processing', 'in_progress', 'in-progress' => 'bg-primary-subtle text-primary border border-primary-subtle',
                                                            'pending' => 'bg-warning-subtle text-warning border border-warning-subtle',
                                                            'failed', 'rejected', 'error' => 'bg-danger-subtle text-danger border border-danger-subtle',
                                                            'query' => 'bg-info-subtle text-info border border-info-subtle',
                                                            default => 'bg-secondary-subtle text-secondary border border-secondary-subtle',
                                                        };
                                                        $icon = match($st) {
                                                            'successful', 'success', 'resolved', 'approved', 'completed' => 'ti-circle-check-filled',
                                                            'processing', 'in_progress', 'in-progress' => 'ti-loader-2',
                                                            'pending' => 'ti-clock-filled',
                                                            'failed', 'rejected', 'error' => 'ti-circle-x-filled',
                                                            'query' => 'ti-help-circle',
                                                            default => 'ti-help-circle',
                                                        };
                                                    @endphp
                                                    <span class="badge rounded-pill px-2 py-1 {{ $badgeClass }}">
                                                        <i class="ti {{ $icon }} me-1"></i>{{ ucfirst($submission->status) }}
                                                    </span>
                                                </td>
                                                <td class="text-end pe-4">
                                                    <div class="d-flex justify-content-end gap-2">
                                                        @php
                                                            $fileUrl = '';
                                                            if (!empty($submission->file_url)) {
                                                                $f = $submission->file_url;
                                                                if (preg_match('/^https?:\/\//', $f)) {
                                                                    $fileUrl = $f;
                                                                } elseif (str_starts_with($f, '/storage') || str_starts_with($f, 'storage')) {
                                                                    $fileUrl = asset(ltrim($f, '/'));
                                                                } else {
                                                                    $fileUrl = \Illuminate\Support\Facades\Storage::url($f);
                                                                }
                                                            }
                                                        @endphp

                                                        <!-- View Comment / Result Modal Button -->
                                                        <button type="button"
                                                                class="btn btn-sm btn-light text-info btn-action-circle shadow-sm"
                                                                title="View Details / Comment"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#commentModal"
                                                                data-comment="{{ $submission->comment ?? 'Your request has been submitted and is in process.' }}"
                                                                data-file-url="{{ $fileUrl }}"
                                                                data-approved-by="{{ $submission->approved_by ?? '' }}">
                                                            <i class="bi bi-eye fs-14"></i>
                                                        </button>

                                                        <!-- Status Refresh Check Button -->
                                                        @if(in_array($submission->status, ['pending', 'processing', 'query']))
                                                            <a href="{{ route('user.nin.personalisation.check', $submission->id) }}"
                                                               class="btn btn-sm btn-light text-primary btn-action-circle shadow-sm check-status-btn"
                                                               title="Check Live Status"
                                                               data-action="check-status">
                                                                <i class="bi bi-arrow-clockwise fs-15 fw-bold"></i>
                                                            </a>
                                                        @endif

                                                        <!-- Download Slip Button -->
                                                        @if($fileUrl)
                                                            <a href="{{ $fileUrl }}"
                                                               class="btn btn-sm btn-light text-success btn-action-circle shadow-sm"
                                                               download
                                                               target="_blank"
                                                               title="Download Slip">
                                                                <i class="bi bi-download fs-14"></i>
                                                            </a>
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="text-center text-muted py-5">
                                                    <div class="py-4">
                                                        <div class="avatar avatar-xl bg-light rounded-circle mb-3 mx-auto d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                                            <i class="ti ti-id-badge-2 fs-2 text-muted"></i>
                                                        </div>
                                                        <h6 class="fw-bold">No Personalisation Requests Found</h6>
                                                        <p class="small text-muted mb-0">Your submitted NIN personalisation requests will appear here.</p>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination -->
                            <div class="p-3 border-top">
                                {{ $submissions->withQueryString()->links('vendor.pagination.custom') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Request Response Modal --}}
    @include('pages.comment')

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // SweetAlert flash messages
            @if(session('status') && session('message'))
                Swal.fire({
                    icon: "{{ session('status') === 'success' ? 'success' : 'error' }}",
                    title: "{{ session('status') === 'success' ? 'Success!' : 'Notice' }}",
                    text: "{{ session('message') }}",
                    timer: 3500,
                    showConfirmButton: false,
                    toast: true,
                    position: 'top-end'
                });
            @endif

            // Status check confirmation
            document.addEventListener('click', function(e) {
                const checkBtn = e.target.closest('[data-action="check-status"]');
                if (checkBtn) {
                    e.preventDefault();
                    const url = checkBtn.getAttribute('href');

                    Swal.fire({
                        title: 'Check Live Status?',
                        text: "This will query the service provider API for current status.",
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Yes, Check Status'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            Swal.fire({
                                title: 'Checking Status...',
                                text: 'Please wait while we communicate with the gateway.',
                                allowOutsideClick: false,
                                didOpen: () => {
                                    Swal.showLoading();
                                }
                            });
                            window.location.href = url;
                        }
                    });
                }
            });
        });

        // Form submit handler with loading state
        function handleFormSubmit(event) {
            const submitBtn = document.getElementById('submitBtn');
            const submitText = document.getElementById('submitText');

            submitBtn.disabled = true;
            submitText.textContent = 'Submitting Request...';

            Swal.fire({
                title: 'Processing Request',
                text: 'Submitting your NIN personalisation request...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            return true;
        }
    </script>
    <style>
        .hover-up:hover {
            transform: translateY(-2px);
            transition: all 0.3s ease;
        }
        .btn-action-circle {
            width: 32px;
            height: 32px;
            border-radius: 50% !important;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0;
            transition: all 0.2s ease;
        }
        .btn-action-circle:hover {
            transform: scale(1.1);
        }
        .fs-11 { font-size: 0.6875rem !important; }
        .fs-12 { font-size: 0.75rem !important; }
        .fs-13 { font-size: 0.8125rem !important; }
        .fs-14 { font-size: 0.875rem !important; }
        .fs-15 { font-size: 0.9375rem !important; }
        .fs-16 { font-size: 1rem !important; }
    </style>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    @endpush
</x-app-layout>
