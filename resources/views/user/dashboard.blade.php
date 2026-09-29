@extends('layouts.dashboard')

@section('title', 'Dashboard')
@push('styles')
    <style>
        /* Default style (for larger screens) */
        .price {
            font-size: 2rem;
            /* Default font size for larger screens */
            white-space: normal;
            /* Allow wrapping on larger screens */
            overflow: visible;
            /* Allow content to overflow if necessary */
            text-overflow: unset;
            /* Reset ellipsis */
            line-height: 1.2;
            /* Standard line height */
        }

        /* Style for smaller screens (e.g., mobile or tablet) */
        @media (max-width: 767px) {
            .price {
                font-size: 1.2rem;
                /* Adjust font size for smaller screens */
                white-space: nowrap;
                /* Prevent text from wrapping */
                overflow: hidden;
                /* Hide overflow */
                text-overflow: ellipsis;
                /* Show ellipsis if text overflows */
            }
        }

        /* General Styles for Service Cards */
        .service-card-body {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .icon-box {
            margin-bottom: 1.5rem;
        }

        .icon-box-media {
            display: flex;
            justify-content: center;
            align-items: center;
            background-color: #5e2572;
            border-radius: 50%;
            width: 70px;
            height: 70px;
        }

        .icon-box-title {
            font-weight: bolder;
            font-size: 1rem;
            color: #333;
        }

        /* Responsive Layout */
        @media (max-width: 768px) {
            .icon-box-media {
                width: 60px;
                height: 60px;
            }

            .icon-box-title {
                font-size: 1rem;
            }
        }

        /* Ensures 2 items per row on mobile (smaller than 576px) */
        @media (max-width: 576px) {
            .col-6 {
                flex: 0 0 50%;
                max-width: 50%;
            }

            .icon-box-media {
                width: 50px;
                height: 50px;
            }

            .icon-box-title {
                font-size: 0.9rem;
            }
        }

        /* Custom CSS for icon box */
        .icon-box-media {
            transition: transform 0.3s ease;
        }

        .icon-box-media:hover {
            transform: scale(1.1);
        }

        /* Service Icon Backgrounds */
        .bg-purple { background-color: #6f42c1 !important; }
        .bg-info { background-color: #0dcaf0 !important; }
        .bg-success { background-color: #198754 !important; }
        .bg-warning { background-color: #ffc107 !important; }
        .bg-danger { background-color: #dc3545 !important; }
        .bg-primary { background-color: #0d6efd !important; }
        .bg-secondary { background-color: #6c757d !important; }
        .bg-dark { background-color: #212529 !important; }

        /* Custom CSS for cards */
        .card {
            transition: box-shadow 0.3s ease;
        }

        .card:hover {
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .copy-btn-wrap .btn {
            padding: 4px 12px;
            font-size: 14px;
            font-weight: 500;
            color: #fff;
            background-color: #007bff;
            /* Bootstrap primary blue */
            border: none;
            border-radius: 6px;
            transition: background-color 0.3s ease;
        }

        .copy-btn-wrap .btn:hover {
            background-color: #0056b3;
            /* Darker blue on hover */
        }
    </style>
    <!-- Latest Material Design Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@mdi/font@7.4.47/css/materialdesignicons.min.css">
@endpush
@section('content')
    <div class="row">
        <div class="mb-3 mt-1">
            <h4 class="mb-1">Welcome back, {{ auth()->user()->name ?? 'User' }} 👋</h4>
            <p class="mb-0">Here’s a quick look at your dashboard.</p>
        </div>
        @if ($status == 'Pending')
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                We're excited to have you on board! However, we need to verify your identity before activating your
                account. Simply click the link below to complete the verification process<br>
            </div>
        @endif
        @include('common.message')
        
        <!-- System Announcement -->
        <div class="col-12 mb-4">
            <div class="card bg-primary text-white border-0 shadow-sm" style="border-radius: 12px; background: linear-gradient(45deg, #5e2572, #8e44ad) !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <i class="mdi mdi-bullhorn-variant-outline mdi-24px me-3"></i>
                        <div>
                            <h6 class="mb-0 fw-bold">System Update</h6>
                            <small>All NIN and BVN services are currently optimized and running at 99.9% success rate. 🚀</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-12 grid-margin d-flex flex-column">
            <div class="row">
                <div class="col-md-6 col-6 grid-margin stretch-card">
                    <div class="card hover-shadow">
                        <div class="card-body text-center">
                            <div class="text-primary mb-2">
                                <i class="mdi mdi-wallet-outline mdi-36px"></i>
                                <p class="fw-medium mt-3">Main Wallet</p>
                            </div>
                            <h1 class="fw-light price">
                                ₦{{ auth()->user()->wallet ? number_format(auth()->user()->wallet->balance, 2) : '0.00' }}
                            </h1>

                            <a href="#" data-bs-toggle="modal" data-bs-target="#walletModal"
                                class="btn btn-sm btn-outline-primary mt-3">
                                Add Fund
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-6 grid-margin stretch-card">
                    <div class="card hover-shadow">
                        <div class="card-body text-center">
                            <div class="text-danger mb-2">
                                <i class="mdi mdi-gift-outline mdi-36px"></i>
                                <p class="fw-medium mt-3">Bonus Wallet</p>
                            </div>
                            <h1 class="fw-light price">
                                ₦{{ auth()->user()->wallet ? number_format(auth()->user()->wallet->bonus, 2) : '0.00' }}
                            </h1>

                            <a href="{{ route('user.wallet') }}" class="btn btn-sm btn-outline-danger mt-3">
                                Claim Bonus
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <!-- Left side column containing the icons -->
                <div class="col-lg-12 col-12 col-md-6">
                    <div class="container py-3" style="max-width: 100%">
                        <h4 class="fw-light mb-4 text-center">Our Services</h4>
                        <div class="row g-4">
                            @php
                                $services = [
                                    ['icon' => 'bi-telephone', 'title' => 'Buy Airtime', 'desc' => 'Recharge instantly', 'route' => 'user.airtime', 'color' => 'bg-primary'],
                                    ['icon' => 'bi-database', 'title' => 'Buy Data', 'desc' => 'Browsing bundles', 'route' => 'user.buy-data', 'color' => 'bg-info'],
                                    ['icon' => 'bi-wifi', 'title' => 'Buy SME Data', 'desc' => 'Cheap bundles', 'route' => 'user.buy-sme-data', 'color' => 'bg-purple'],
                                    ['icon' => 'bi-person-check', 'title' => 'NIN Verification', 'desc' => 'Verify NIN Slip', 'route' => 'user.nin.verification.index', 'color' => 'bg-success'],
                                    ['icon' => 'bi-phone', 'title' => 'NIN Phone Verify', 'desc' => 'Linked numbers', 'route' => 'user.nin.phone.index', 'color' => 'bg-warning'],
                                    ['icon' => 'bi-person-vcard', 'title' => 'NIN Demo Verify', 'desc' => 'Name & DOB', 'route' => 'user.nin.demo.index', 'color' => 'bg-secondary'],
                                    ['icon' => 'bi-shield-check', 'title' => 'BVN Verification', 'desc' => 'Secure check', 'route' => 'user.bvn-verification', 'color' => 'bg-danger'],
                                    ['icon' => 'bi-patch-check', 'title' => 'NIN Validation', 'desc' => 'Confirm records', 'route' => 'user.nin.validation.index', 'color' => 'bg-info'],
                                    ['icon' => 'bi-person-badge', 'title' => 'NIN Personalisation', 'desc' => 'Card request', 'route' => 'user.nin.personalisation.index', 'color' => 'bg-primary'],
                                    ['icon' => 'bi-person-gear', 'title' => 'NIN Modification', 'desc' => 'Update details', 'route' => 'user.nin.modification.index', 'color' => 'bg-danger'],
                                    ['icon' => 'bi-bank', 'title' => 'BVN Modification', 'desc' => 'Bank records', 'route' => 'user.modification', 'color' => 'bg-dark'],
                                    ['icon' => 'bi-headset', 'title' => 'BVN CRM Service', 'desc' => 'Check status', 'route' => 'user.bvn-crm', 'color' => 'bg-primary'],
                                    ['icon' => 'bi-search', 'title' => 'BVN Phone Search', 'desc' => 'Phone search', 'route' => 'user.phone.search.index', 'color' => 'bg-info'],
                                    ['icon' => 'bi-file-earmark-check', 'title' => 'IPE Clearance', 'desc' => 'Clearance check', 'route' => 'user.ipe.index', 'color' => 'bg-purple'],
                                    ['icon' => 'bi-percent', 'title' => 'TIN Verification', 'desc' => 'Tax identification', 'route' => 'user.tin.index', 'color' => 'bg-success'],
                                ];
                            @endphp

                            @foreach($services as $svc)
                                <div class="col-6 col-md-4 col-lg-3">
                                    <div class="card shadow-sm border-0 h-100 service-card hover-shadow" style="border-radius: 15px;">
                                        <div class="card-body text-center p-3">
                                            <div class="icon-box-media mx-auto mb-3 {{ $svc['color'] }} bg-gradient d-flex align-items-center justify-content-center rounded-circle shadow-sm" style="width: 60px; height: 60px;">
                                                <i class="bi {{ $svc['icon'] }} text-white" style="font-size: 24px;"></i>
                                            </div>
                                            <h6 class="fw-bold mb-1" style="font-size: 0.85rem;">{{ $svc['title'] }}</h6>
                                            <p class="text-muted mb-0" style="font-size: 0.7rem;">{{ $svc['desc'] }}</p>
                                            <a href="{{ route($svc['route']) }}" class="stretched-link"></a>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Right side column for transaction table -->
                <div class="col-lg-12 stretch-card mt-">
                    <div class="container py-3" style="max-width: 100%">
                        <h4 class="fw-light mb-4 text-center">Recent Transactions</h4>
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="table-responsive">
                                    @php
                                        $transactions = auth()->user()->transactions()->latest()->paginate(10);
                                        $serialNumber =
                                            ($transactions->currentPage() - 1) * $transactions->perPage() + 1;
                                    @endphp

                                    @forelse ($transactions as $data)
                                        @if ($loop->first)
                                            <table class="table text-nowrap" style="background: #fafafc !important;">
                                                <thead>
                                                    <tr class="table-primary">
                                                        <th width="5%">ID</th>
                                                        <th>Reference No.</th>
                                                        <th>Service Type</th>
                                                        <th>Description</th>
                                                        <th>Amount</th>
                                                        <th class="text-center">Status</th>
                                                        <th class="text-center">Receipt</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                        @endif

                                        <tr>
                                            <td>{{ $serialNumber++ }}</td>
                                            <td>
                                                @if($data->referenceId)
                                                <a target="_blank"
                                                    href="{{ route('user.reciept', $data->referenceId) }}">
                                                    {{ strtoupper($data->referenceId) }}
                                                </a>
                                                @else
                                                <span class="text-muted">N/A</span>
                                                @endif
                                            </td>
                                            <td>{{ $data->service_type }}</td>
                                            <td>{{ $data->service_description }}</td>
                                            <td>&#8358;{{ number_format($data->amount, 2) }}</td>
                                            <td class="text-center">
                                                <span
                                                    class="badge
                                                    {{ $data->status == 'Approved' ? 'bg-success' : ($data->status == 'Rejected' ? 'bg-danger' : 'bg-warning') }}">
                                                    {{ strtoupper($data->status) }}
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                @if($data->referenceId)
                                                <a target="_blank" href="{{ route('user.reciept', $data->referenceId) }}"
                                                    class="btn btn-outline-primary btn-sm">
                                                    <i class="bi bi-download"></i> Download
                                                </a>
                                                @else
                                                <button disabled class="btn btn-outline-secondary btn-sm">
                                                    <i class="bi bi-slash-circle"></i> No Receipt
                                                </button>
                                                @endif
                                            </td>
                                        </tr>

                                        @if ($loop->last)
                                            </tbody>
                                            </table>

                                            <div class="d-flex justify-content-center mt-3">
                                                {{ $transactions->appends(request()->query())->links('vendor.pagination.bootstrap-5') }}
                                            </div>
                                        @endif
                                    @empty
                                        <div class="text-center">
                                            <p class="fw-semibold fs-15 mt-2">No Transaction Available!</p>
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal fade" id="kycModal" tabindex="-1" aria-labelledby="kycModal" data-bs-backdrop="static" data-bs-keyboard="false">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h6 class="modal-title" id="staticBackdropLabel2">Verify Account</h6>
                        </div>

                        <div class="modal-body">
                            We're excited to have you on board! To activate your account and create your virtual account, please provide the information below.
                        </div>

                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        @if (session('error'))
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                {{ session('error') }}
                            </div>
                        @endif

                        <div class="px-4 pb-4">
                            <form id="verify" name="verifyForm" method="POST" action="{{ route('user.kyc.submit') }}">
                                @csrf

                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <label for="first_name" class="form-label small">First Name</label>
                                        <input type="text" id="first_name" name="first_name" class="form-control" value="{{ old('first_name', auth()->user()->first_name ?? '') }}" required />
                                    </div>

                                    <div class="col-md-6">
                                        <label for="last_name" class="form-label small">Last Name</label>
                                        <input type="text" id="last_name" name="last_name" class="form-control" value="{{ old('last_name', auth()->user()->last_name ?? '') }}" required />
                                    </div>

                                    <div class="col-md-6">
                                        <label for="phone" class="form-label small">Phone Number</label>
                                        <input type="tel" id="phone" name="phone" class="form-control" value="{{ old('phone', auth()->user()->phone_number ?? '') }}" maxlength="15" required />
                                    </div>

                                    <div class="col-md-6">
                                        <label for="email" class="form-label small">Email</label>
                                        <input type="email" id="email" name="email" class="form-control" value="{{ old('email', auth()->user()->email ?? '') }}" required />
                                    </div>

                                    <div class="col-md-6">
                                        <label for="bvn" class="form-label small">BVN</label>
                                        <input type="text" id="bvn" name="bvn" class="form-control text-center" maxlength="11" value="{{ old('bvn', auth()->user()->bvn ?? '') }}" required />
                                    </div>

                                    <div class="col-md-6">
                                        <label for="dob" class="form-label small">Date of Birth</label>
                                        <input type="date" id="dob" name="dob" class="form-control" value="{{ old('dob', auth()->user()->dob ?? '') }}" required />
                                    </div>
                                </div>

                                <div class="text-center mt-3 d-flex justify-content-center gap-2">
                                    <button type="submit" id="submit" class="btn btn-primary">
                                        <i class="lar la-check-circle"></i> Compile & Create Account
                                    </button>
                            </form>
                                    <form method="POST" action="{{ route('logout') }}" class="m-0">
                                        @csrf
                                        <button type="submit" class="btn btn-danger">
                                            <i class="las la-sign-out-alt"></i> Logout
                                        </button>
                                    </form>
                                </div>
                        </div>

                    </div>
                </div>
            </div>

        @include('user.dashboard_modals')
    @endsection
    @push('scripts')
        <script>
            @if ($kycPending)
                const kycModal = new bootstrap.Modal(document.getElementById('kycModal'));
                kycModal.show();
            @endif

            document.addEventListener('DOMContentLoaded', function() {
                const form = document.getElementById('verify');
                const submitButton = document.getElementById('submit');

                if (form && submitButton) {
                    form.addEventListener('submit', function() {
                        submitButton.disabled = true;
                        submitButton.innerText = 'Verifying ...';
                    });
                }
            });
        </script>
    @endpush
