<x-app-layout>
    <title>Hanan Verify - {{ $title ?? 'BVN Modification' }}</title>
    <div class="page-body">
        <div class="container-fluid">
            <div class="page-title mb-3">
                <div class="row">
                    <div class="col-sm-6 col-12">
                        <h3 class="fw-bold text-primary">BVN Modification Request</h3>
                        <p class="text-muted small mb-0">Follow NIBSS regulations for data updates accurately.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="container-fluid mt-3">
            <div class="row">
                <!-- BVN Modification Form -->
                <div class="col-xl-6 mb-4">
                    <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
                        <div class="card-header bg-primary text-white py-3">
                            <h5 class="mb-0 fw-bold text-white"><i class="bi bi-pencil-square me-2"></i>Modification Form</h5>
                        </div>

                        <div class="card-body p-4">
                            {{-- Alerts --}}
                            @if (session('message'))
                                <div class="alert alert-{{ session('status') === 'success' ? 'success' : 'danger' }} alert-dismissible fade show border-0 shadow-sm mb-4">
                                    <i class="bi bi-{{ session('status') === 'success' ? 'check-circle' : 'exclamation-triangle' }} me-2"></i>
                                    {{ session('message') }}
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

                            <form method="POST" action="{{ route('user.modification.store') }}" enctype="multipart/form-data" class="row g-4">
                                @csrf

                                <!-- Bank & Field Selection -->
                                <div class="col-md-6">
                                    <label for="enrolment_bank" class="form-label fw-bold">Select Bank <span class="text-danger">*</span></label>
                                    <select name="enrolment_bank" id="enrolment_bank" class="form-select border-primary-subtle" required>
                                        <option value="">-- Select Enrolment Bank --</option>
                                        @foreach($bankServices as $service)
                                            <option value="{{ $service->id }}" {{ old('enrolment_bank') == $service->id ? 'selected' : '' }}>
                                                {{ $service->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label for="service_field" class="form-label fw-bold">Modification Field <span class="text-danger">*</span></label>
                                    <select name="service_field" id="service_field" class="form-select border-primary-subtle" required>
                                        <option value="">-- Select Field --</option>
                                        <!-- Options will be loaded dynamically via AJAX -->
                                    </select>
                                    <div class="mt-2 text-end">
                                        <small class="text-muted fst-italic" id="field-description"></small>
                                    </div>
                                </div>

                                <!-- BVN & NIN Row -->
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">BVN <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light"><i class="bi bi-shield-lock"></i></span>
                                        <input class="form-control" name="bvn" type="text" required
                                               placeholder="11-digit BVN"
                                               value="{{ old('bvn') }}" maxlength="11" minlength="11"
                                               pattern="[0-9]{11}">
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-bold">NIN <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light"><i class="bi bi-person-badge"></i></span>
                                        <input class="form-control" name="nin" type="text" required
                                               placeholder="11-digit NIN"
                                               value="{{ old('nin') }}" maxlength="11" minlength="11"
                                               pattern="[0-9]{11}">
                                    </div>
                                </div>

                                <!-- New Data Description -->
                                <div class="col-12">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <label class="form-label fw-bold mb-0">New Data Information <span class="text-danger">*</span></label>
                                        <button type="button" class="btn btn-outline-primary btn-sm py-0" data-bs-toggle="modal" data-bs-target="#sampleInfoModal">
                                            <i class="bi bi-info-circle"></i> View Samples
                                        </button>
                                    </div>
                                    <textarea name="description" rows="3" class="form-control border-primary-subtle" placeholder="State exactly what needs to be changed (e.g. Correct Date of Birth from 1990 to 1992)" required>{{ old('description') }}</textarea>
                                </div>

                                <!-- Affidavit Selection -->
                                <div class="col-12">
                                    <label class="form-label fw-bold text-dark">Affidavit Requirement <span class="text-danger">*</span></label>
                                    <div class="p-3 bg-light rounded-3 border border-warning-subtle">
                                        <p class="small text-warning-emphasis fw-semibold mb-2">
                                            <i class="bi bi-info-square-fill me-1"></i> Note: We charge an additional fee of ₦{{ number_format($affidavitPrice) }} if you don't have an affidavit.
                                        </p>
                                        <select name="affidavit" id="affidavit" class="form-select" data-price="{{ $affidavitPrice }}" required>
                                            <option value="">-- Choose Affidavit Status --</option>
                                            <option value="available" {{ old('affidavit') === 'available' ? 'selected' : '' }}>I have an Affidavit (Upload below)</option>
                                            <option value="not_available" {{ old('affidavit') === 'not_available' ? 'selected' : '' }}>I don't have an Affidavit (Chargable)</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Affidavit Upload -->
                                <div class="col-12" id="affidavit_upload_wrapper" style="display: none;">
                                    <label class="form-label fw-bold">Upload Affidavit (PDF only)</label>
                                    <input type="file" name="affidavit_file" accept="application/pdf" class="form-control border-primary-subtle">
                                    <small class="text-muted">Max file size: 5MB</small>
                                </div>

                                <!-- Pricing Info Row -->
                                <div class="col-md-6 text-center">
                                    <label class="form-label fw-bold">Field Fee</label>
                                    <div class="alert alert-secondary py-2 border-0 shadow-sm mb-0">
                                        <span class="h5 fw-bold mb-0 text-primary" id="field-price">₦0.00</span>
                                    </div>
                                </div>

                                <div class="col-md-6 text-center">
                                    <label class="form-label fw-bold">Total Payable</label>
                                    <div class="alert alert-soft-warning py-2 border-0 shadow-sm mb-0">
                                        <span class="h5 fw-bold mb-0 text-dark" id="total-amount">₦0.00</span>
                                    </div>
                                    <small class="text-muted d-block mt-1" id="fee-breakdown"></small>
                                </div>

                                <!-- Wallet Balance -->
                                <div class="col-12">
                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted fw-semibold">Your Wallet Balance:</span>
                                        <span class="text-success fw-bold">₦{{ number_format($wallet->balance ?? 0, 2) }}</span>
                                    </div>
                                </div>

                                <!-- Terms Checkbox -->
                                <div class="col-12">
                                    <div class="form-check custom-checkbox">
                                        <input type="checkbox" class="form-check-input" id="termsCheck" required>
                                        <label class="form-check-label small" for="termsCheck">
                                            I agree to the BVN modification policies and confirm the accuracy of this data.
                                        </label>
                                    </div>
                                </div>

                                <!-- Submit Button -->
                                <div class="col-12 d-grid">
                                    <button type="submit" class="btn btn-primary btn-lg shadow-sm hover-up">
                                        <i class="bi bi-cloud-upload me-2"></i> Submit Modification Request
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Submission History -->
                <div class="col-xl-6">
                    <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
                        <div class="card-header bg-white border-bottom py-3">
                            <h5 class="mb-0 fw-bold text-dark">
                                <i class="bi bi-clock-history me-2 text-primary"></i> Submission History
                            </h5>
                        </div>
                        <div class="card-body p-4">
                            <form class="row g-3 mb-4 bg-light p-3 rounded-3 border" method="GET" action="{{ route('user.modification') }}">
                                <div class="col-md-5">
                                    <input class="form-control border-0 shadow-sm" name="search" type="text" placeholder="Request ID..." value="{{ request('search') }}">
                                </div>
                                <div class="col-md-4">
                                    <select class="form-select border-0 shadow-sm" name="status">
                                        <option value="">All Statuses</option>
                                        @foreach(['pending', 'processing', 'successful', 'query', 'resolved', 'rejected', 'remark'] as $status)
                                            <option value="{{ $status }}" {{ request('status') == $status ? 'selected' : '' }}>
                                                {{ ucfirst($status) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <button class="btn btn-primary w-100 shadow-sm" type="submit">
                                        <i class="bi bi-filter"></i> Filter
                                    </button>
                                </div>
                            </form>

                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="bg-primary text-white">
                                        <tr>
                                            <th>#</th>
                                            <th>Reference</th>
                                            <th>Bank</th>
                                            <th>BVN</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($crmSubmissions as $submission)
                                            <tr>
                                                <td class="fw-bold text-muted">{{ $loop->iteration + $crmSubmissions->firstItem() - 1 }}</td>
                                                <td><span class="text-primary fw-medium">{{ $submission->reference }}</span></td>
                                                <td>{{ $submission->bank }}</td>
                                                <td><span class="badge bg-secondary-subtle text-secondary border">{{ $submission->bvn }}</span></td>
                                                <td>
                                                    <span class="badge rounded-pill bg-{{ match($submission->status) {
                                                        'resolved', 'successful' => 'success',
                                                        'processing' => 'primary',
                                                        'query' => 'info',
                                                        'rejected', 'failed', 'error' => 'danger',
                                                        'remark' => 'secondary',
                                                        default => 'warning'
                                                    } }}">{{ ucfirst($submission->status) }}</span>
                                                </td>
                                                <td>
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
                                                    <div class="d-flex gap-1">
                                                        <button type="button"
                                                                class="btn btn-sm btn-icon btn-outline-primary"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#commentModal"
                                                                data-comment="{{ $submission->comment ?? 'No comment yet.' }}"
                                                                data-file-url="{{ $fileUrl }}">
                                                            <i class="bi bi-eye-fill"></i>
                                                        </button>

                                                        @if(!in_array($submission->status, ['successful', 'rejected']))
                                                            <a href="{{ route('user.modification.check', $submission->id) }}" 
                                                               class="btn btn-sm btn-icon btn-outline-success" 
                                                               title="Sync Status">
                                                                <i class="bi bi-arrow-clockwise"></i>
                                                            </a>
                                                        @elseif($submission->status === 'successful' && $fileUrl)
                                                            <a href="{{ $fileUrl }}" 
                                                               class="btn btn-sm btn-icon btn-success" 
                                                               target="_blank" 
                                                               title="Download Result">
                                                                <i class="bi bi-download"></i>
                                                            </a>
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="text-center text-muted py-5">
                                                    <i class="bi bi-inbox fs-1 d-block mb-3"></i>
                                                    No modification requests found.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            <div class="mt-4 d-flex justify-content-center">
                                {{ $crmSubmissions->withQueryString()->links('vendor.pagination.custom') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Comment Modal --}}
        @include('pages.comment')

        <!-- Guidelines Modal -->
        <div class="modal fade" id="sampleInfoModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content border-0 shadow rounded-4">
                    <div class="modal-header bg-primary text-white py-3">
                        <h5 class="modal-title fw-bold"><i class="bi bi-info-circle me-2"></i>Modification Guidelines</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="bg-primary-subtle p-3 rounded-3 mb-4">
                            <h6 class="fw-bold text-primary mb-2">Instructions:</h6>
                            <ul class="list-unstyled mb-0">
                                <li class="mb-1"><i class="bi bi-check-circle-fill text-success me-2"></i> Clearly state the current and new information.</li>
                                <li class="mb-1"><i class="bi bi-check-circle-fill text-success me-2"></i> Provide a clear reason for the change.</li>
                                <li><i class="bi bi-check-circle-fill text-success me-2"></i> Upload a valid affidavit if you have one.</li>
                            </ul>
                        </div>

                        <div class="p-3 mb-4 bg-white border rounded-3 shadow-sm">
                            <h6 class="fw-bold text-secondary mb-3"><i class="bi bi-lightbulb-fill text-warning me-2"></i>Sample Content:</h6>
                            <div class="font-monospace small p-2 bg-light rounded">
                                "Correct Date of Birth from 15th June 1985 to 15th June 1988. Reason: Error during initial enrollment."
                            </div>
                        </div>

                        <div class="alert alert-info border-0 shadow-sm d-flex">
                            <i class="bi bi-info-circle-fill fs-4 me-3"></i>
                            <div>
                                <strong>Note:</strong> All submission are reviewed within 24-48 business hours by NIBSS.
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-primary rounded-pill px-4" data-bs-dismiss="modal">Understood</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .hover-up:hover { transform: translateY(-3px); transition: all 0.3s ease; }
        .alert-soft-warning { background-color: #fff3cd; color: #664d03; }
        .btn-icon { width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; }
        .table thead th { font-weight: 600; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.5px; }
    </style>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <script src="{{ asset('assets/js/bvnservices.js') }}"></script>


    <script>
        document.addEventListener("DOMContentLoaded", function() {
            @if (session('success'))
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: "{{ session('success') }}",
                    confirmButtonColor: '#3085d6',
                });
            @endif

            @if (session('error'))
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: "{{ session('error') }}",
                    confirmButtonColor: '#d33',
                });
            @endif

            @if (session('status') && session('message'))
                Swal.fire({
                    icon: "{{ session('status') === 'success' ? 'success' : 'error' }}",
                    title: "{{ session('status') === 'success' ? 'Great!' : 'Oops!' }}",
                    text: "{{ session('message') }}",
                    confirmButtonColor: '#3085d6',
                });
            @endif
        });
    </script>
</x-app-layout>