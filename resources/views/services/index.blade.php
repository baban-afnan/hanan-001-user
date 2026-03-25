<x-app-layout>
      <title>Hanan Verify - Services Management</title>

    <div class="content">
        <!-- Page Header -->
        <div class="page-header mb-4">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h3 class="page-title text-primary mb-1 fw-bold">Services Management</h3>
                    <ul class="breadcrumb bg-transparent p-0 mb-0">
                        <li class="breadcrumb-item active text-muted">
                            Manage system services, fields, and pricing configurations.
                        </li>
                    </ul>
                </div>
                <div class="col-md-4 text-md-end mt-3 mt-md-0">
                    <button class="btn btn-primary" style="background-color: #0db4bd; border: none;" data-bs-toggle="modal" data-bs-target="#addServiceModal">
                        <i class="mdi mdi-plus me-1"></i> Add Service
                    </button>
                </div>
            </div>
        </div>

        <!-- Stats -->
        <div class="row g-3 mb-4">
            <!-- Total Services -->
            <div class="col-xl-4 col-md-6 fade-in-up" style="animation-delay: 0.1s;">
                <div class="financial-card shadow-sm h-100 p-4" style="background: var(--primary-gradient);">
                    <div class="d-flex justify-content-between align-items-start position-relative z-1">
                        <div>
                            <p class="stats-label mb-1" style="color: white;">Total Services</p>
                            <h3 class="stats-value mb-0 text-white">{{ $totalServicesCount }}</h3>
                        </div>
                        <div class="avatar avatar-lg bg-white bg-opacity-25 rounded-3">
                            <i class="mdi mdi-layers-outline text-white" style="font-size: 30px;"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Active Services -->
            <div class="col-xl-4 col-md-6 fade-in-up" style="animation-delay: 0.2s;">
                <div class="financial-card shadow-sm h-100 p-4" style="background: var(--success-gradient);">
                    <div class="d-flex justify-content-between align-items-start position-relative z-1">
                        <div>
                            <p class="stats-label mb-1" style="color: white;">Active Services</p>
                            <h3 class="stats-value mb-0 text-white">{{ $activeServicesCount }}</h3>
                        </div>
                        <div class="avatar avatar-lg bg-white bg-opacity-25 rounded-3">
                            <i class="mdi mdi-pulse text-white" style="font-size: 30px;"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Inactive Services -->
            <div class="col-xl-4 col-md-6 fade-in-up" style="animation-delay: 0.3s;">
                <div class="financial-card shadow-sm h-100 p-4" style="background: var(--danger-gradient);">
                    <div class="d-flex justify-content-between align-items-start position-relative z-1">
                        <div>
                            <p class="stats-label mb-1" style="color: white;">Inactive Services</p>
                            <h3 class="stats-value mb-0 text-white">{{ $inactiveServicesCount }}</h3>
                        </div>
                        <div class="avatar avatar-lg bg-white bg-opacity-25 rounded-3">
                            <i class="mdi mdi-layers-off-outline text-white" style="font-size: 30px;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        {{-- Alerts --}}
        @if(session('success'))
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: "{{ session('success') }}",
                        timer: 3000,
                        showConfirmButton: true,
                        confirmButtonColor: '#6366f1',
                    });
                });
            </script>
        @endif

        @if(session('error'))
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: "{{ session('error') }}",
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#ef4444',
                    });
                });
            </script>
        @endif

        
      <!-- Services Table Card -->
<div class="card border-0 shadow-sm">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap row-gap-3 bg-white border-bottom py-3">
        <h5 class="card-title mb-0 fw-bold">System Services</h5>
        <div class="d-flex my-xl-auto right-content align-items-center flex-wrap row-gap-3">
            <div class="me-3">
                <form action="{{ route('admin.services.index') }}" method="GET">
                    <div class="input-icon-end position-relative">
                        <input type="text" name="search" class="form-control" placeholder="Search services..." value="{{ request('search') }}">
                        <span class="input-icon-addon position-absolute end-0 top-50 translate-middle-y me-3">
                            <i class="mdi mdi-magnify"></i>
                        </span>
                        @if(request('status'))
                            <input type="hidden" name="status" value="{{ request('status') }}">
                        @endif
                        @if(request('sort'))
                            <input type="hidden" name="sort" value="{{ request('sort') }}">
                        @endif
                    </div>
                </form>
            </div>
            <div class="dropdown me-3">
                <a href="javascript:void(0);" class="dropdown-toggle btn btn-white d-inline-flex align-items-center border" data-bs-toggle="dropdown">
                    {{ request('status') ? ucfirst(request('status')) : 'Select Status' }}
                </a>
                <ul class="dropdown-menu dropdown-menu-end p-3">
                    <li><a href="{{ route('admin.services.index', array_merge(request()->except('page'), ['status' => 'active'])) }}" class="dropdown-item rounded-1">Active</a></li>
                    <li><a href="{{ route('admin.services.index', array_merge(request()->except('page'), ['status' => 'inactive'])) }}" class="dropdown-item rounded-1">Inactive</a></li>
                    <li><a href="{{ route('admin.services.index', array_merge(request()->except('page', 'status'))) }}" class="dropdown-item rounded-1">All</a></li>
                </ul>
            </div>
            <div class="dropdown">
                <a href="javascript:void(0);" class="dropdown-toggle btn btn-white d-inline-flex align-items-center border" data-bs-toggle="dropdown">
                    Sort By : {{ request('sort') == 'oldest' ? 'Oldest' : 'Newest' }}
                </a>
                <ul class="dropdown-menu dropdown-menu-end p-3">
                    <li><a href="{{ route('admin.services.index', array_merge(request()->except('page'), ['sort' => 'newest'])) }}" class="dropdown-item rounded-1">Newest</a></li>
                    <li><a href="{{ route('admin.services.index', array_merge(request()->except('page'), ['sort' => 'oldest'])) }}" class="dropdown-item rounded-1">Oldest</a></li>
                </ul>
            </div>
        </div>
    </div>

    <div class="card-body p-0">
        <div class="custom-datatable-filter table-responsive">
            <table class="table datatable table-hover align-middle mb-0">
                <thead class="thead-light bg-light">
                    <tr>
                        <th class="ps-4">S/N</th>
                        <th>Service Name</th>
                        <th>Fields</th>
                        <th>Prices</th>
                        <th>Created Date</th>
                        <th>Status</th>
                        <th class="text-end pe-4">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($services as $service)
                        <tr>
                            <td class="ps-4">
                                <span class="fw-semibold text-muted">{{ $services->firstItem() + $loop->index }}</span>
                            </td>
                            <td>
                                <div class="d-flex align-items-center file-name-icon">
                                    @if($service->image)
                                        <a href="#" class="avatar avatar-md border rounded-circle">
                                            <img src="{{ $service->image }}" class="img-fluid rounded-circle" alt="img">
                                        </a>
                                    @else
                                        <a href="#" class="avatar avatar-md border rounded-circle bg-soft-primary text-primary d-flex align-items-center justify-content-center">
                                            <span class="fs-10 fw-bold">{{ strtoupper(substr($service->name, 0, 1)) }}</span>
                                        </a>
                                    @endif
                                    <div class="ms-2">
                                        <h6 class="fw-medium mb-0">
                                            <a href="{{ route('admin.services.show', $service) }}" class="text-dark">{{ $service->name }}</a>
                                        </h6>
                                    </div>
                                </div>
                            </td>
                           
                            <td>
                                <span class="badge bg-soft-info text-info">{{ $service->fields_count }} Fields</span>
                            </td>
                            <td>
                                <span class="badge bg-soft-warning text-warning">{{ $service->prices_count }} Prices</span>
                            </td>
                            <td>{{ $service->created_at?->format('d M Y') ?? 'N/A' }}</td>
                            <td>
                                @if($service->is_active)
                                    <span class="badge bg-soft-success d-flex align-items-center badge-xs text-success">
                                        <i class="mdi mdi-circle me-1" style="font-size: 8px;"></i>Active
                                    </span>
                                @else
                                    <span class="badge bg-soft-danger d-flex align-items-center badge-xs text-danger">
                                        <i class="mdi mdi-circle me-1" style="font-size: 8px;"></i>Inactive
                                    </span>
                                @endif
                            </td>
                            <td class="text-end pe-4">
                                <div class="action-icon d-inline-flex">
                                    <a href="{{ route('admin.services.show', $service) }}" class="me-2 text-primary" title="Configure"><i class="mdi mdi-cog-outline fs-18"></i></a>
                                    <a href="#"
                                       class="me-2 edit-service-btn text-info"
                                       data-bs-toggle="modal"
                                       data-bs-target="#editServiceModal"
                                       data-id="{{ $service->id }}"
                                       data-name="{{ $service->name }}"
                                       data-description="{{ $service->description }}"
                                       data-image="{{ $service->image ?? '' }}"
                                       data-is-active="{{ $service->is_active }}"
                                       title="Edit">
                                        <i class="mdi mdi-pencil fs-18"></i>
                                    </a>
                                    <button type="button" class="btn btn-link p-0 text-danger" onclick="confirmDelete('{{ $service->id }}', '{{ $service->name }}')" title="Delete">
                                        <i class="mdi mdi-delete fs-18"></i>
                                    </button>
                                    <form id="delete-form-{{ $service->id }}" action="{{ route('admin.services.destroy', $service) }}" method="POST" class="d-none">
                                        @csrf
                                        @method('DELETE')
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <div class="d-flex flex-column align-items-center">
                                    <i class="ti ti-server-off fs-1 mb-2 opacity-50"></i>
                                    <p class="mb-0">No services found. Create one to get started.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card-footer bg-white border-top-0 py-3">
        {{ $services->links('vendor.pagination.custom') }}
    </div>
</div>

    <!-- Add Service Modal -->
    <div class="modal fade" id="addServiceModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold">Add New Service</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('admin.services.store') }}" method="POST" enctype="multipart/form-data" id="addServiceForm">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3 text-center">
                            <div class="avatar avatar-xl bg-soft-secondary text-secondary rounded-circle mb-2 d-flex align-items-center justify-content-center mx-auto">
                                <i class="mdi mdi-image-outline" style="font-size: 40px;"></i>
                            </div>
                            <div class="mt-2">
                                <label class="btn btn-sm btn-soft-primary" for="addImage">
                                    <i class="mdi mdi-upload me-1"></i> Upload Icon
                                </label>
                                <input type="file" name="image" id="addImage" class="d-none" accept="image/*">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Service Name</label>
                            <input type="text" name="name" class="form-control" placeholder="e.g., BVN Enrollment" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="3" placeholder="Brief description of the service..."></textarea>
                        </div>
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="addStatus" checked>
                                <label class="form-check-label" for="addStatus">Active Status</label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-top-0">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create Service</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Single Edit Service Modal -->
    <div class="modal fade" id="editServiceModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold">Edit Service</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editServiceForm" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <div class="mb-3 text-center" id="edit-image-container">
                            <img id="editServiceImage" src="" alt="Current Image" class="rounded-circle mb-2 mx-auto" width="80" height="80" style="display:none;">
                            <div class="mt-2">
                                <label class="btn btn-sm btn-soft-primary" for="editImage">
                                    <i class="mdi mdi-camera me-1"></i> Change Icon
                                </label>
                                <input type="file" name="image" id="editImage" class="d-none" accept="image/*">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Service Name</label>
                            <input type="text" name="name" id="editServiceName" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" id="editServiceDescription" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" id="editStatus">
                                <label class="form-check-label" for="editStatus">Active Status</label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-top-0">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Service</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- JS to populate edit modal -->
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const editButtons = document.querySelectorAll('.edit-service-btn');
        const form = document.getElementById('editServiceForm');
        const nameInput = document.getElementById('editServiceName');
        const descInput = document.getElementById('editServiceDescription');
        const imageTag = document.getElementById('editServiceImage');
        const statusInput = document.getElementById('editStatus');

        editButtons.forEach(btn => {
            btn.addEventListener('click', function () {
                const id = this.dataset.id;
                const name = this.dataset.name;
                const description = this.dataset.description;
                const image = this.dataset.image;
                const isActive = this.dataset.isActive === '1' || this.dataset.isActive === 'true';

                form.action = `/admin/services/${id}`;
                nameInput.value = name;
                descInput.value = description || '';
                statusInput.checked = isActive;

                if(image){
                    imageTag.src = image;
                    imageTag.style.display = 'inline-block';
                } else {
                    imageTag.style.display = 'none';
                }
            });
        });
    });
    </script>

        <style>
            :root {
                --primary-gradient: linear-gradient(135deg, #0db4bd 0%, #09848a 100%);
                --success-gradient: linear-gradient(135deg, #22c55e 0%, #10b981 100%);
                --info-gradient: linear-gradient(135deg, #3b82f6 0%, #0ea5e9 100%);
                --warning-gradient: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
                --danger-gradient: linear-gradient(135deg, #ef4444 0%, #f43f5e 100%);
            }

            /* Financial Cards */
            .financial-card {
                position: relative;
                overflow: hidden;
                border: none;
                border-radius: 1rem;
                color: white;
            }
            .financial-card::before {
                content: '';
                position: absolute;
                top: 0;
                right: 0;
                width: 150px;
                height: 150px;
                background: rgba(255, 255, 255, 0.1);
                border-radius: 50%;
                transform: translate(30%, -30%);
            }
            .financial-card::after {
                content: '';
                position: absolute;
                bottom: 0;
                left: 0;
                width: 100px;
                height: 100px;
                background: rgba(255, 255, 255, 0.1);
                border-radius: 50%;
                transform: translate(-30%, 30%);
            }
            
            /* Stats Typography */
            .stats-label { font-size: 0.875rem; font-weight: 500; opacity: 0.9; }
            .stats-value { font-size: 1.5rem; font-weight: 700; letter-spacing: -0.025em; }

            /* Animation */
            @keyframes fadeIn {
                from { opacity: 0; transform: translateY(10px); }
                to { opacity: 1; transform: translateY(0); }
            }
            .fade-in-up {
                animation: fadeIn 0.5s ease-out forwards;
            }
            
            .avatar-lg { width: 3rem; height: 3rem; display: flex; align-items: center; justify-content: center; }
        </style>

    <!-- Tabler Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    
    {{-- SweetAlert CDN --}}


    <script>
        // Form confirmation helper
        function confirmAction(formId, message) {
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
                    document.getElementById(formId).submit();
                }
            });
            return false;
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Add Service Form
            const addForm = document.getElementById('addServiceForm');
            if(addForm) {
                addForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    confirmAction('addServiceForm');
                });
            }

            // Edit Service Form
            const editForm = document.getElementById('editServiceForm');
            if(editForm) {
                editForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    confirmAction('editServiceForm');
                });
            }
        });

        function confirmDelete(id, name) {
            Swal.fire({
                title: 'Are you sure?',
                text: `You are about to delete ${name}. This action cannot be undone!`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('delete-form-' + id).submit();
                }
            });
        }
    </script>
</x-app-layout>
