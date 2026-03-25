<x-app-layout>
   <title>Hanan Verify - Services Management</title>

    <div class="content">
        <!-- Page Header -->
        <div class="page-header mb-4">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <div class="d-flex align-items-center">
                        <a href="{{ route('admin.services.index') }}" class="btn btn-icon btn-sm btn-light rounded-circle me-3">
                            <i class="mdi mdi-arrow-left"></i>
                        </a>
                        <div>
                            <h3 class="page-title text-primary mb-1 fw-bold">{{ $service->name }}</h3>
                            <ul class="breadcrumb bg-transparent p-0 mb-0">
                                <li class="breadcrumb-item text-muted">Service Management</li>
                                <li class="breadcrumb-item active text-primary">Configuration</li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 text-md-end mt-3 mt-md-0">
                    <span class="badge bg-{{ $service->is_active ? 'success' : 'danger' }} fs-12">
                        {{ $service->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Service Fields Section -->
            <div class="col-lg-12">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0 fw-bold"><i class="mdi mdi-format-list-bulleted me-2 text-primary"></i>Service Fields</h5>
                        <button class="btn btn-sm btn-primary" style="background-color: #0db4bd; border: none;" data-bs-toggle="modal" data-bs-target="#addFieldModal">
                            <i class="mdi mdi-plus me-1"></i> Add Field
                        </button>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="ps-4">S/N</th>
                                        <th>Field Name</th>
                                        <th>Code</th>
                                        <th>Base Price</th>
                                        <th>Status</th>
                                        <th class="text-end pe-4">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($fields as $field)
                                        <tr>
                                            <td class="ps-4 fw-medium text-muted">{{ $fields->firstItem() + $loop->index }}</td>
                                            <td class="fw-medium">{{ $field->field_name }}</td>
                                            <td><code class="text-primary">{{ $field->field_code }}</code></td>
                                            <td>₦{{ number_format($field->base_price, 2) }}</td>
                                            <td>
                                                @if($field->is_active)
                                                    <span class="badge bg-soft-success text-success">Active</span>
                                                @else
                                                    <span class="badge bg-soft-danger text-danger">Inactive</span>
                                                @endif
                                            </td>
                                            <td class="text-end pe-4">
                                                <button class="btn btn-icon btn-sm btn-soft-info rounded-circle me-1" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#editFieldModal{{ $field->id }}">
                                                    <i class="mdi mdi-pencil"></i>
                                                </button>
                                                <button type="button" 
                                                        class="btn btn-icon btn-sm btn-soft-danger rounded-circle"
                                                        onclick="confirmFieldDelete('{{ $field->id }}', '{{ $field->field_name }}')">
                                                    <i class="mdi mdi-delete"></i>
                                                </button>
                                                <form id="delete-field-form-{{ $field->id }}" action="{{ route('admin.services.fields.destroy', $field) }}" method="POST" class="d-none">
                                                    @csrf
                                                    @method('DELETE')
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center py-4 text-muted">No fields defined yet.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @if($fields->hasPages())
                        <div class="card-footer bg-white border-top-0 py-3">
                             {{ $fields->links('vendor.pagination.custom') }}
                        </div>
                    @endif
                </div>
            </div>

            <!-- Service Prices Section -->
            <div class="col-lg-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0 fw-bold"><i class="mdi mdi-currency-ngn me-2 text-success"></i>Pricing Configuration</h5>
                        <button class="btn btn-sm btn-success text-white" style="background-color: #2ecc71; border: none;" data-bs-toggle="modal" data-bs-target="#addPriceModal">
                            <i class="mdi mdi-plus me-1"></i> Add Price
                        </button>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="ps-4">S/N</th>
                                        <th>User Type</th>
                                        <th>Linked Field</th>
                                        <th>Price</th>
                                        <th class="text-end pe-4">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($prices as $price)
                                        <tr>
                                            <td class="ps-4 fw-medium text-muted">{{ $prices->firstItem() + $loop->index }}</td>
                                            <td class="text-capitalize fw-medium">{{ $price->user_type }}</td>
                                            <td>
                                                @if($price->field)
                                                    <span class="badge bg-light text-dark border">{{ $price->field->field_name }}</span>
                                                @else
                                                    <span class="text-muted small">Base Service Price</span>
                                                @endif
                                            </td>
                                            <td class="fw-bold text-dark">₦{{ number_format($price->price, 2) }}</td>
                                            <td class="text-end pe-4">
                                                <button class="btn btn-icon btn-sm btn-soft-info rounded-circle me-1" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#editPriceModal{{ $price->id }}">
                                                    <i class="mdi mdi-pencil"></i>
                                                </button>
                                                <button type="button" 
                                                        class="btn btn-icon btn-sm btn-soft-danger rounded-circle"
                                                        onclick="confirmPriceDelete('{{ $price->id }}', '{{ $price->user_type }}')">
                                                    <i class="mdi mdi-delete"></i>
                                                </button>
                                                <form id="delete-price-form-{{ $price->id }}" action="{{ route('admin.services.prices.destroy', $price) }}" method="POST" class="d-none">
                                                    @csrf
                                                    @method('DELETE')
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center py-4 text-muted">No pricing configurations set.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                     @if($prices->hasPages())
                        <div class="card-footer bg-white border-top-0 py-3">
                             {{ $prices->links('vendor.pagination.custom') }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Add Field Modal -->
    <div class="modal fade" id="addFieldModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold">Add New Field</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('admin.services.fields.store', $service) }}" method="POST" id="addFieldForm">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Field Name</label>
                            <input type="text" name="field_name" class="form-control" placeholder="e.g., Standard Enrollment" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Field Code</label>
                            <input type="text" name="field_code" class="form-control" placeholder="e.g., STD_ENROLL" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Base Price</label>
                            <input type="number" step="0.01" name="base_price" class="form-control" placeholder="0.00" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" checked>
                            <label class="form-check-label">Active</label>
                        </div>
                    </div>
                    <div class="modal-footer border-top-0">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Field</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Field Modals (moved outside table) -->
    @foreach($fields as $field)
    <div class="modal fade" id="editFieldModal{{ $field->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold">Edit Field</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('admin.services.fields.update', $field) }}" method="POST" class="edit-field-form">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Field Name</label>
                            <input type="text" name="field_name" class="form-control" value="{{ $field->field_name }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">
                                Field Code 
                                <span class="badge bg-warning bg-opacity-10 text-warning ms-2">
                                    <i class="mdi mdi-lock me-1"></i>Read-Only
                                </span>
                            </label>
                            <input type="text" name="field_code" class="form-control bg-light" value="{{ $field->field_code }}" readonly required>
                            <small class="text-muted">
                                <i class="ti ti-info-circle me-1"></i>Field codes cannot be changed after creation for data integrity.
                            </small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Base Price</label>
                            <input type="number" step="0.01" name="base_price" class="form-control" value="{{ $field->base_price }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="2">{{ $field->description }}</textarea>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" {{ $field->is_active ? 'checked' : '' }}>
                            <label class="form-check-label">Active</label>
                        </div>
                    </div>
                    <div class="modal-footer border-top-0">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endforeach

    <!-- Add Price Modal -->
    <div class="modal fade" id="addPriceModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold">Add Price Configuration</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('admin.services.prices.store', $service) }}" method="POST" id="addPriceForm">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Linked Field (Optional)</label>
                            <select name="service_fields_id" class="form-select">
                                <option value="">-- Base Service Price --</option>
                                @foreach($service->fields as $field)
                                    <option value="{{ $field->id }}">{{ $field->field_name }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted">Leave empty to set a base price for the service itself.</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">User Type</label>
                            <select name="user_type" class="form-select" required>
                                <option value="personal">Personal</option>
                                <option value="agent">Agent</option>
                                <option value="partner">Partner</option>
                                <option value="business">Business</option>
                                <option value="staff">Staff</option>
                                <option value="api">API</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Price</label>
                            <input type="number" step="0.01" name="price" class="form-control" placeholder="0.00" required>
                        </div>
                    </div>
                    <div class="modal-footer border-top-0">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success text-white">Add Price</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Price Modals (moved outside table) -->
    @foreach($prices as $price)
    <div class="modal fade" id="editPriceModal{{ $price->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold">Edit Price</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('admin.services.prices.update', $price) }}" method="POST" class="edit-price-form">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">User Type</label>
                            <select name="user_type" class="form-select" required>
                                <option value="personal" {{ $price->user_type == 'personal' ? 'selected' : '' }}>Personal</option>
                                <option value="agent" {{ $price->user_type == 'agent' ? 'selected' : '' }}>Agent</option>
                                <option value="partner" {{ $price->user_type == 'partner' ? 'selected' : '' }}>Partner</option>
                                <option value="business" {{ $price->user_type == 'business' ? 'selected' : '' }}>Business</option>
                                <option value="staff" {{ $price->user_type == 'staff' ? 'selected' : '' }}>Staff</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Price</label>
                            <input type="number" step="0.01" name="price" class="form-control" value="{{ $price->price }}" required>
                        </div>
                    </div>
                    <div class="modal-footer border-top-0">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endforeach

    <!-- Tabler Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">

    @push('scripts')
    <script>
        // ===== SweetAlert Success/Error Messages =====
        @if(session('success'))
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: "{{ session('success') }}",
                showConfirmButton: true,
                confirmButtonColor: '#0db4bd',
                timer: 3500,
            });
        @endif

        @if(session('error'))
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: "{{ session('error') }}",
                confirmButtonColor: '#ef4444',
                confirmButtonText: 'OK',
                customClass: {
                    confirmButton: 'btn btn-danger'
                },
                buttonsStyling: false
            });
        @endif

        // Form confirmation helper
        function confirmAction(form, message) {
            Swal.fire({
                title: 'Are you sure?',
                text: "Are you sure you want to do this? If yes, click then continue.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#0db4bd',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, continue!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Add Field Form
            const addFieldForm = document.getElementById('addFieldForm');
            if(addFieldForm) {
                addFieldForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    confirmAction(this);
                });
            }

            // Edit Field Forms
            document.querySelectorAll('.edit-field-form').forEach(form => {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    confirmAction(this);
                });
            });

            // Add Price Form
            const addPriceForm = document.getElementById('addPriceForm');
            if(addPriceForm) {
                addPriceForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    confirmAction(this);
                });
            }

            // Edit Price Forms
            document.querySelectorAll('.edit-price-form').forEach(form => {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    confirmAction(this);
                });
            });
        });

        // ===== Delete Field Confirmation =====
        function confirmFieldDelete(id, name) {
            Swal.fire({
                title: 'Delete Field?',
                html: `Are you sure you want to delete <strong>${name}</strong>?<br><small class="text-muted">This may affect existing transactions and cannot be undone.</small>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: '<i class="ti ti-trash me-1"></i> Yes, Delete',
                cancelButtonText: '<i class="ti ti-x me-1"></i> Cancel',
                reverseButtons: true,
                customClass: {
                    confirmButton: 'btn btn-danger me-2',
                    cancelButton: 'btn btn-secondary'
                },
                buttonsStyling: false
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Deleting...',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    document.getElementById('delete-field-form-' + id).submit();
                }
            });
        }

        // ===== Delete Price Confirmation =====
        function confirmPriceDelete(id, userType) {
            Swal.fire({
                title: 'Delete Price Configuration?',
                html: `Are you sure you want to delete the price for <strong>${userType}</strong> users?<br><small class="text-muted">This action cannot be undone.</small>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: '<i class="ti ti-trash me-1"></i> Yes, Delete',
                cancelButtonText: '<i class="ti ti-x me-1"></i> Cancel',
                reverseButtons: true,
                customClass: {
                    confirmButton: 'btn btn-danger me-2',
                    cancelButton: 'btn btn-secondary'
                },
                buttonsStyling: false
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Deleting...',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    document.getElementById('delete-price-form-' + id).submit();
                }
            });
        }
    </script>
    @endpush

</x-app-layout>
