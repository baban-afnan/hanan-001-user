<x-app-layout>
    <title>Hanan Verify - {{ $title ?? 'CRM Request Form' }}</title>
    <div class="page-body">
        <div class="container-fluid">
            <div class="page-title mb-3">
                <div class="row">
                    <div class="col-sm-6 col-12">
                        <h3 class="fw-bold text-primary">CRM on Failed Enrolment</h3>
                        <p class="text-muted small mb-0">Submit your request accurately to ensure smooth processing.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="container-fluid mt-3">
            <div class="row">
                <!-- BVN CRM Form -->
                <div class="col-xl-6 mb-4">
                    <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
                        <div class="card-header bg-primary text-white py-3">
                            <h5 class="mb-0 fw-bold text-white"><i class="bi bi-gear-wide-connected me-2"></i>BVN CRM Request</h5>
                        </div>

                        <div class="card-body p-4">
                            {{-- Alerts --}}
                            @if (session('status'))
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

                            <div class="text-center mb-4">
                                <p class="text-muted">
                                    Please provide the Batch and Ticket IDs from your failed NIBSS enrollment.
                                </p>
                            </div>

                            {{-- BVN CRM Request Form --}}
                            <form method="POST" action="{{ route('user.crm.store') }}" class="row g-4">
                                @csrf

                                <!-- CRM Type -->
                                <div class="col-md-12">
                                    <label class="form-label fw-bold">CRM Type <span class="text-danger">*</span></label>
                                    <select class="form-select border-primary-subtle" name="field_code" id="crm_service_field" required>
                                        <option value="">-- Select CRM Category --</option>
                                        @foreach ($fieldname as $field)
                                            @php
                                                $price = $field->prices
                                                    ->where('user_type', auth()->user()->role)
                                                    ->first()?->price ?? $field->base_price;
                                            @endphp
                                            <option value="{{ $field->id }}"
                                                    data-price="{{ $price }}"
                                                    data-description="{{ $field->description }}"
                                                    {{ old('field_code') == $field->id ? 'selected' : '' }}>
                                                {{ $field->field_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="mt-2">
                                        <small class="text-muted fst-italic" id="crm-field-description"></small>
                                    </div>
                                </div>

                                <!-- IDs Row -->
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Batch ID <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light"><i class="bi bi-hash"></i></span>
                                        <input class="form-control" name="batch_id" type="text" required
                                               placeholder="7 digits"
                                               value="{{ old('batch_id') }}" maxlength="7" minlength="7"
                                               pattern="[0-9]{7}">
                                        <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#sampleInfoModal">
                                            <i class="bi bi-question-circle"></i>
                                        </button>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Ticket ID <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light"><i class="bi bi-ticket-perforated"></i></span>
                                        <input class="form-control" name="ticket_id" type="text" required
                                               placeholder="8 digits"
                                               value="{{ old('ticket_id') }}" maxlength="8" minlength="8"
                                               pattern="[0-9]{8}">
                                    </div>
                                </div>

                                <!-- Pricing Info -->
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Service Fee</label>
                                    <div class="alert alert-secondary py-2 mb-0 text-center border-0 shadow-sm">
                                        <span class="h5 fw-bold mb-0 text-primary" id="crm-field-price">₦0.00</span>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Wallet Balance</label>
                                    <div class="alert alert-soft-success py-2 mb-0 text-center border-0 shadow-sm">
                                        <span class="h5 fw-bold mb-0 text-success">₦{{ number_format($wallet->balance ?? 0, 2) }}</span>
                                    </div>
                                </div>

                                <!-- Terms -->
                                <div class="col-12 mt-2">
                                    <div class="form-check custom-checkbox">
                                        <input class="form-check-input" id="termsCheckbox" type="checkbox" required>
                                        <label class="form-check-label small" for="termsCheckbox">
                                            By submitting, I certify that the IDs provided are from a failed enrollment and belong to the correct individual.
                                        </label>
                                    </div>
                                </div>

                                <!-- Submit -->
                                <div class="col-12 d-grid">
                                    <button type="submit" class="btn btn-primary btn-lg shadow-sm hover-up">
                                        <i class="bi bi-cloud-upload me-2"></i> Submit CRM Request
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
                            <h5 class="fw-bold mb-0 text-dark">
                                <i class="bi bi-clock-history me-2 text-primary"></i> CRM Submission History
                            </h5>
                        </div>

                        <div class="card-body p-4">
                            <!-- Filter Form -->
                            <form method="GET" class="row g-3 mb-4 bg-light p-3 rounded-3 border">
                                <div class="col-md-5">
                                    <input class="form-control border-0 shadow-sm"
                                           name="search"
                                           type="text"
                                           placeholder="Ticket/Batch ID..."
                                           value="{{ request('search') }}">
                                </div>
                                <div class="col-md-4">
                                    <select class="form-select border-0 shadow-sm" name="status">
                                        <option value="">All Statuses</option>
                                        @foreach(['pending','processing','successful','query','resolved','rejected','remark'] as $status)
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

                            <!-- Table -->
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="bg-primary text-white">
                                        <tr>
                                            <th>#</th>
                                            <th>Reference</th>
                                            <th>Ticket ID</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($submissions as $submission)
                                            <tr>
                                                <td class="fw-bold text-muted">{{ $loop->iteration + $submissions->firstItem() - 1 }}</td>
                                                <td><span class="text-primary fw-medium">{{ $submission->reference }}</span></td>
                                                <td><span class="badge bg-secondary-subtle text-secondary border">{{ $submission->ticket_id ?? $submission->batch_id ?? 'N/A' }}</span></td>
                                                <td>
                                                    <span class="badge rounded-pill bg-{{ match($submission->status) {
                                                        'resolved', 'successful' => 'success',
                                                        'processing'             => 'primary',
                                                        'rejected'               => 'danger',
                                                        'query'                  => 'info',
                                                        'remark'                 => 'secondary',
                                                        default                  => 'warning'
                                                    } }}">
                                                        {{ ucfirst($submission->status) }}
                                                    </span>
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
                                                    <div class="d-flex gap-2">
                                                        <button type="button"
                                                                class="btn btn-sm btn-icon btn-outline-primary"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#commentModal"
                                                                data-comment="{{ $submission->comment ?? 'No comment yet.' }}"
                                                                data-file-url="{{ $fileUrl }}">
                                                            <i class="bi bi-eye-fill"></i>
                                                        </button>

                                                        @if($submission->status === 'pending' || $submission->status === 'processing' || $submission->status === 'query')
                                                            <a href="{{ route('user.crm.check', $submission->id) }}" 
                                                               class="btn btn-sm btn-icon btn-outline-info" 
                                                               title="Check Status">
                                                                <i class="bi bi-arrow-clockwise"></i>
                                                            </a>
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="text-center text-muted py-5">
                                                    <i class="bi bi-inbox fs-1 d-block mb-3"></i>
                                                    No CRM submissions found matching your criteria.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination -->
                            <div class="mt-4 d-flex justify-content-center">
                                {{ $submissions->withQueryString()->links('vendor.pagination.custom') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Guidelines Modal -->
    <div class="modal fade" id="sampleInfoModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow rounded-4">
                <div class="modal-header bg-primary text-white py-3">
                    <h5 class="modal-title fw-bold"><i class="bi bi-info-circle me-2"></i>Submission Guidelines</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-4">
                        <h6 class="fw-bold text-dark">How to find your IDs:</h6>
                        <ul class="list-group list-group-flush border-0">
                            <li class="list-group-item border-0 px-0 d-flex align-items-start">
                                <i class="bi bi-1-circle-fill text-primary me-3 mt-1"></i>
                                <span>Go to your <strong>Failed Enrollments</strong> history in your agent portal.</span>
                            </li>
                            <li class="list-group-item border-0 px-0 d-flex align-items-start">
                                <i class="bi bi-2-circle-fill text-primary me-3 mt-1"></i>
                                <span>Identify the specific record and look for the response code from NIBSS.</span>
                            </li>
                            <li class="list-group-item border-0 px-0 d-flex align-items-start">
                                <i class="bi bi-3-circle-fill text-primary me-3 mt-1"></i>
                                <span>Copy the <strong>Batch ID (7 digits)</strong> and <strong>Ticket ID (8 digits)</strong> precisely.</span>
                            </li>
                        </ul>
                    </div>
                    <div class="alert alert-warning border-0 shadow-sm d-flex">
                        <i class="bi bi-exclamation-triangle-fill fs-4 me-3"></i>
                        <div>
                            <strong>Attention:</strong> Incorrect IDs will result in automatic rejection and may still incur a processing fee as per policy.
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-primary rounded-pill px-4" data-bs-dismiss="modal">Got it!</button>
                </div>
            </div>
        </div>
    </div>

    @include('pages.comment')

    <style>
        .hover-up:hover { transform: translateY(-3px); transition: all 0.3s ease; }
        .alert-soft-success { background-color: #d1e7dd; color: #0f5132; }
        .btn-icon { width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; }
        .table thead th { font-weight: 600; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.5px; }
    </style>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const crmSelect = document.getElementById('crm_service_field');
            const priceDisplay = document.getElementById('crm-field-price');
            const descDisplay = document.getElementById('crm-field-description');

            if (crmSelect) {
                crmSelect.addEventListener('change', function() {
                    if (this.value) {
                        const selectedOption = this.options[this.selectedIndex];
                        const price = parseFloat(selectedOption.dataset.price || 0);
                        const description = selectedOption.dataset.description || '';

                        priceDisplay.textContent = '₦' + price.toLocaleString('en-NG', {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        });
                        descDisplay.textContent = description;
                    } else {
                        priceDisplay.textContent = '₦0.00';
                        descDisplay.textContent = '';
                    }
                });

                // Trigger change event to set initial value (e.g., if there's an old value selected)
                if (crmSelect.value) {
                    crmSelect.dispatchEvent(new Event('change'));
                }
            }

            @if (session('status') && session('message'))
                Swal.fire({
                    icon: "{{ session('status') === 'success' ? 'success' : 'error' }}",
                    title: "{{ session('status') === 'success' ? 'Success!' : 'Oops!' }}",
                    text: "{{ session('message') }}",
                    confirmButtonColor: '#3085d6',
                });
            @endif

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
        });
    </script>
</x-app-layout>
