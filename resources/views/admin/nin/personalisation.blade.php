@extends('layouts.dashboard')

@section('title', 'Admin - NIN Personalisation Management')

@push('styles')
<style>
    .avatar-stat {
        width: 48px;
        height: 48px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
        font-size: 22px;
    }
    .hover-card {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .hover-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.08) !important;
    }
    .btn-circle {
        width: 34px;
        height: 34px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0;
    }
    .tracking-code {
        font-family: 'Courier New', Courier, monospace;
        letter-spacing: 0.5px;
    }
</style>
@endpush

@section('content')
<div class="row">
    <!-- Header Title -->
    <div class="col-12 mb-3 mt-1">
        <div class="d-flex flex-wrap justify-content-between align-items-center">
            <div>
                <h4 class="fw-bold mb-1 text-dark">NIN Personalisation Management</h4>
                <p class="text-muted small mb-0">Monitor, check live status, and manage all user NIN Personalisation submissions.</p>
            </div>
            <div class="d-flex gap-2 mt-2 mt-md-0">
                <form action="{{ route('admin.services.nin-personalisation.batch-check') }}" method="POST" class="m-0" id="batchCheckForm">
                    @csrf
                    <button type="submit" class="btn btn-primary btn-sm shadow-sm" onclick="this.disabled=true; this.innerHTML='<span class=\"spinner-border spinner-border-sm me-1\"></span> Checking...'; this.form.submit();">
                        <i class="mdi mdi-refresh-circle me-1"></i> Batch Check (10)
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Summary Statistics Cards -->
    <div class="col-12 mb-4">
        <div class="row g-3">
            <div class="col-xl-2 col-md-4 col-sm-6">
                <div class="card border-0 shadow-sm rounded-4 hover-card h-100">
                    <div class="card-body p-3 d-flex align-items-center">
                        <div class="avatar-stat bg-primary bg-opacity-10 text-primary me-3">
                            <i class="mdi mdi-card-account-details-outline"></i>
                        </div>
                        <div>
                            <span class="text-muted small fw-semibold text-uppercase">All Requests</span>
                            <h4 class="fw-bold mb-0 text-dark">{{ number_format($total_request) }}</h4>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-2 col-md-4 col-sm-6">
                <div class="card border-0 shadow-sm rounded-4 hover-card h-100">
                    <div class="card-body p-3 d-flex align-items-center">
                        <div class="avatar-stat bg-warning bg-opacity-10 text-warning me-3">
                            <i class="mdi mdi-clock-outline"></i>
                        </div>
                        <div>
                            <span class="text-muted small fw-semibold text-uppercase">Pending</span>
                            <h4 class="fw-bold mb-0 text-warning">{{ number_format($pending) }}</h4>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-4 col-sm-6">
                <div class="card border-0 shadow-sm rounded-4 hover-card h-100">
                    <div class="card-body p-3 d-flex align-items-center">
                        <div class="avatar-stat bg-info bg-opacity-10 text-info me-3">
                            <i class="mdi mdi-progress-clock"></i>
                        </div>
                        <div>
                            <span class="text-muted small fw-semibold text-uppercase">Processing</span>
                            <h4 class="fw-bold mb-0 text-info">{{ number_format($processing) }}</h4>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 col-sm-6">
                <div class="card border-0 shadow-sm rounded-4 hover-card h-100">
                    <div class="card-body p-3 d-flex align-items-center">
                        <div class="avatar-stat bg-success bg-opacity-10 text-success me-3">
                            <i class="mdi mdi-check-decagram-outline"></i>
                        </div>
                        <div>
                            <span class="text-muted small fw-semibold text-uppercase">Successful</span>
                            <h4 class="fw-bold mb-0 text-success">{{ number_format($successful) }}</h4>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-2 col-md-6 col-sm-6">
                <div class="card border-0 shadow-sm rounded-4 hover-card h-100">
                    <div class="card-body p-3 d-flex align-items-center">
                        <div class="avatar-stat bg-danger bg-opacity-10 text-danger me-3">
                            <i class="mdi mdi-alert-circle-outline"></i>
                        </div>
                        <div>
                            <span class="text-muted small fw-semibold text-uppercase">Failed / Query</span>
                            <h4 class="fw-bold mb-0 text-danger">{{ number_format($rejected) }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Table & Filter Card -->
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold text-primary">
                    <i class="mdi mdi-format-list-bulleted me-2"></i>Personalisation Requests
                </h5>
                <span class="badge bg-light text-primary border px-3 py-2 rounded-pill">
                    {{ $submissions->total() }} Total Records
                </span>
            </div>

            <div class="card-body p-4">
                {{-- Flash Message Alerts --}}
                @if (session('status'))
                    <div class="alert alert-{{ session('status') === 'success' ? 'success' : (session('status') === 'info' ? 'info' : 'danger') }} alert-dismissible fade show border-0 shadow-sm mb-4">
                        <i class="mdi mdi-{{ session('status') === 'success' ? 'check-circle' : 'information' }} me-2"></i>
                        {{ session('message') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4">
                        <ul class="mb-0 small">
                            @foreach ($errors->all() as $err)
                                <li>{{ $err }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <!-- Filter Box -->
                <form method="GET" action="{{ route('admin.services.nin-personalisation.index') }}" class="p-3 bg-light rounded-3 border mb-4">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label small fw-bold text-muted">Search</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-white border-end-0"><i class="mdi mdi-magnify text-muted"></i></span>
                                <input class="form-control border-start-0" name="search" type="text"
                                       placeholder="Tracking ID, Ref, Name, Email..."
                                       value="{{ request('search') }}">
                            </div>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label small fw-bold text-muted">Status</label>
                            <select class="form-select form-select-sm" name="status">
                                <option value="">All Statuses</option>
                                @foreach(['pending','processing','successful','query','resolved','rejected','failed','remark'] as $st)
                                    <option value="{{ $st }}" {{ request('status') === $st ? 'selected' : '' }}>
                                        {{ ucfirst($st) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label small fw-bold text-muted">From Date</label>
                            <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
                        </div>

                        <div class="col-md-2">
                            <label class="form-label small fw-bold text-muted">To Date</label>
                            <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
                        </div>

                        <div class="col-md-3 d-flex align-items-end gap-2">
                            <button class="btn btn-primary btn-sm flex-grow-1 shadow-sm" type="submit">
                                <i class="mdi mdi-filter me-1"></i> Filter
                            </button>
                            @if(request()->hasAny(['search', 'status', 'date_from', 'date_to']))
                                <a href="{{ route('admin.services.nin-personalisation.index') }}" class="btn btn-outline-secondary btn-sm shadow-sm" title="Clear Filters">
                                    <i class="mdi mdi-refresh"></i>
                                </a>
                            @endif
                        </div>
                    </div>
                </form>

                <!-- Data Table -->
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center" style="width: 50px;">#</th>
                                <th>Reference / Date</th>
                                <th>User Information</th>
                                <th>Tracking ID</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th class="text-center" style="width: 140px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($submissions as $submission)
                                <tr>
                                    <td class="text-center text-muted fw-bold">
                                        {{ $loop->iteration + $submissions->firstItem() - 1 }}
                                    </td>
                                    <td>
                                        <span class="text-primary fw-bold d-block">{{ $submission->reference }}</span>
                                        <small class="text-muted">
                                            <i class="mdi mdi-calendar-clock me-1"></i>{{ $submission->created_at->format('M d, Y h:i A') }}
                                        </small>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <span class="fw-bold text-dark">{{ $submission->user->name ?? 'N/A' }}</span>
                                            <small class="text-muted">{{ $submission->user->email ?? 'N/A' }}</small>
                                            @if(!empty($submission->user->phone))
                                                <small class="text-muted"><i class="mdi mdi-phone me-1"></i>{{ $submission->user->phone }}</small>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border px-2 py-1 tracking-code fs-13">
                                            {{ $submission->tracking_id ?? 'N/A' }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="fw-bold text-dark">₦{{ number_format($submission->amount ?? 1500, 2) }}</span>
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
                                        @endphp
                                        <span class="badge rounded-pill px-3 py-1 {{ $badgeClass }}">
                                            {{ strtoupper($submission->status) }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex gap-1 justify-content-center">
                                            <!-- Details / Override Modal Trigger -->
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-primary btn-circle"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#editModal{{ $submission->id }}"
                                                    title="View & Manage">
                                                <i class="mdi mdi-pencil-outline"></i>
                                            </button>

                                            <!-- Live Status Check -->
                                            <a href="{{ route('admin.services.nin-personalisation.check', $submission->id) }}"
                                               class="btn btn-sm btn-info text-white btn-circle"
                                               title="Check Live Status"
                                               onclick="return confirm('Query Arewa Smart API live status for Tracking ID: {{ $submission->tracking_id }}?');">
                                                <i class="mdi mdi-refresh"></i>
                                            </a>

                                            <!-- Download File if available -->
                                            @php
                                                $fUrl = '';
                                                if (!empty($submission->file_url)) {
                                                    $f = $submission->file_url;
                                                    if (preg_match('/^https?:\/\//', $f)) {
                                                        $fUrl = $f;
                                                    } elseif (str_starts_with($f, '/storage') || str_starts_with($f, 'storage')) {
                                                        $fUrl = asset(ltrim($f, '/'));
                                                    } else {
                                                        $fUrl = \Illuminate\Support\Facades\Storage::url($f);
                                                    }
                                                }
                                            @endphp
                                            @if($fUrl)
                                                <a href="{{ $fUrl }}"
                                                   target="_blank"
                                                   download
                                                   class="btn btn-sm btn-success text-white btn-circle"
                                                   title="Download Result File">
                                                    <i class="mdi mdi-download"></i>
                                                </a>
                                            @endif
                                        </div>

                                        <!-- Edit / Override Modal -->
                                        <div class="modal fade" id="editModal{{ $submission->id }}" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content text-start border-0 shadow rounded-4 overflow-hidden">
                                                    <div class="modal-header bg-primary text-white py-3">
                                                        <h5 class="modal-title text-white fw-bold mb-0">
                                                            <i class="mdi mdi-card-account-details-outline me-2"></i>Request Details #{{ $submission->reference }}
                                                        </h5>
                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                    </div>

                                                    <div class="modal-body p-4">
                                                        <!-- Request Meta Info -->
                                                        <div class="bg-light p-3 rounded-3 mb-3 border">
                                                            <div class="row g-2 small">
                                                                <div class="col-6">
                                                                    <span class="text-muted d-block">User:</span>
                                                                    <strong>{{ $submission->user->name ?? 'N/A' }}</strong>
                                                                </div>
                                                                <div class="col-6">
                                                                    <span class="text-muted d-block">Tracking ID:</span>
                                                                    <strong class="tracking-code text-primary">{{ $submission->tracking_id }}</strong>
                                                                </div>
                                                                <div class="col-6 mt-2">
                                                                    <span class="text-muted d-block">Amount Charged:</span>
                                                                    <strong class="text-success">₦{{ number_format($submission->amount ?? 1500, 2) }}</strong>
                                                                </div>
                                                                <div class="col-6 mt-2">
                                                                    <span class="text-muted d-block">Submission Date:</span>
                                                                    <span>{{ $submission->created_at->format('M d, Y H:i') }}</span>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <!-- Update Form -->
                                                        <form action="{{ route('admin.services.nin-personalisation.update-status', $submission->id) }}"
                                                              method="POST"
                                                              enctype="multipart/form-data">
                                                            @csrf

                                                            <div class="mb-3">
                                                                <label class="form-label small fw-bold">Manual Status Override <span class="text-danger">*</span></label>
                                                                <select name="status" class="form-select form-select-sm" required>
                                                                    @foreach(['pending','processing','successful','query','resolved','rejected','remark','failed'] as $st)
                                                                        <option value="{{ $st }}" {{ $submission->status === $st ? 'selected' : '' }}>
                                                                            {{ strtoupper($st) }}
                                                                        </option>
                                                                    @endforeach
                                                                </select>
                                                            </div>

                                                            <div class="mb-3">
                                                                <label class="form-label small fw-bold">Comment / Reason / Result Note</label>
                                                                <textarea name="comment" class="form-control" rows="3" placeholder="Enter notes or explanation for the user...">{{ $submission->comment }}</textarea>
                                                            </div>

                                                            <div class="mb-3">
                                                                <label class="form-label small fw-bold">Direct File URL (optional)</label>
                                                                <input type="url" name="file_url" class="form-control form-control-sm"
                                                                       placeholder="https://..." value="{{ $submission->file_url }}">
                                                            </div>

                                                            <div class="mb-3">
                                                                <label class="form-label small fw-bold">Or Upload Slip / Document</label>
                                                                <input type="file" name="slip" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png">
                                                                <div class="form-text small">Accepted: PDF, JPG, PNG (Max 5MB)</div>
                                                            </div>

                                                            <div class="d-grid mt-4">
                                                                <button type="submit" class="btn btn-primary">
                                                                    <i class="mdi mdi-content-save me-1"></i> Save Changes
                                                                </button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-5">
                                        <i class="mdi mdi-inbox fs-1 d-block mb-2 text-muted"></i>
                                        <h6 class="fw-bold">No Personalisation Requests Found</h6>
                                        <p class="small text-muted mb-0">Submissions from agents and users will be displayed here.</p>
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
@endsection
