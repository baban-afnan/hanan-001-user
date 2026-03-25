<x-app-layout>
    <title>Hanan Verify - Buy JAMB PIN</title>

    <div class="row">
        <div class="col-xxl-12 col-xl-12">
            <div class="row mt-3">
                <!-- Left Column: Purchase Form -->
                <div class="col-xl-4 mb-3">
                    <div class="card custom-card shadow-sm border-0">
                        <div class="card-header justify-content-between bg-primary text-white rounded-top">
                            <div class="card-title fw-semibold">
                                <i class="bi bi-book-half me-2"></i> Buy JAMB PIN
                            </div>
                        </div>
                        <div class="card-body">

                            <center class="mb-3">
                                <img class="img-fluid" src="{{ asset('assets/img/apps/jamb.jpg') }}" width="35%" onerror="this.src='{{ asset('assets/img/apps/pin.png') }}'">
                            </center>

                            <p class="text-center text-muted mb-4">
                                Select your JAMB service, verify your Profile ID, and proceed to purchase your PIN securely.
                            </p>

                            {{-- Alert Messages --}}
                            @if (session('success'))
                                <div class="alert alert-success alert-dismissible fade show text-center" role="alert">
                                    {!! session('success') !!}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            @endif

                            @if (session('error'))
                                <div class="alert alert-danger alert-dismissible fade show text-center" role="alert">
                                    {{ session('error') }}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            @endif

                            <form id="buy-jamb-form" method="POST" action="{{ route('buyjamb') }}">
                                @csrf
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <label class="form-label fw-semibold mb-0">Select Package</label>
                                        <button type="button" class="btn btn-sm btn-link text-decoration-none p-0" id="refresh-plans">
                                            <i class="bi bi-arrow-clockwise"></i> Refresh Plans
                                        </button>
                                    </div>
                                    <select name="service" id="service_id" class="form-select text-center" required>
                                        <option value="">-- Select Package --</option>
                                        @foreach($variations as $variation)
                                            <option value="{{ $variation->variation_code }}">{{ $variation->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Profile ID</label>
                                    <div class="input-group">
                                        <input type="text" id="profile_id" name="profile_id" class="form-control text-center" placeholder="Enter Profile ID" required>
                                        <button class="btn btn-outline-primary" type="button" id="verify-btn">Verify</button>
                                    </div>
                                    <small id="verify-status" class="d-block mt-1 fw-bold"></small>
                                </div>

                                <div id="customer-info" class="alert alert-info d-none">
                                    <div class="fw-bold">Customer Name: <span id="customer-name" class="text-primary"></span></div>
                                </div>

                                {{-- Amount --}}
                                <div class="mb-4 text-start">
                                    <label for="amount" class="form-label fw-semibold d-flex justify-content-between">
                                        <span>Amount</span>
                                        <small class="text-muted">Balance: 
                                            <strong class="text-success">
                                                ₦{{ number_format($wallet->balance ?? 0, 2) }}
                                            </strong>
                                        </small>
                                    </label>
                                    <input type="text" id="amountToPay" name="amount" readonly class="form-control text-center" value="0.00" />
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Phone Number</label>
                                    <input type="text" id="mobileno" name="mobileno" maxlength="11"
                                           class="form-control text-center" placeholder="Enter 11-digit number" required>
                                </div>

                                <div class="d-grid mt-4">
                                    <button type="button" class="btn btn-primary btn-lg fw-semibold" id="proceed-btn" disabled
                                        data-bs-toggle="modal" data-bs-target="#pinModal">
                                        Proceed to Buy
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Purchase History -->
                <div class="col-xl-8 d-none d-md-block">
                    <div class="card custom-card shadow-sm border-0">
                        <div class="card-header justify-content-between bg-light rounded-top">
                            <div class="card-title fw-semibold">
                                <i class="bi bi-list-task me-2 text-primary"></i> JAMB Purchase History
                            </div>
                        </div>
                        <div class="card-body">
                            <p class="text-muted mb-3">Below is your recent JAMB PIN purchase history:</p>

                            @if (isset($history) && !$history->isEmpty())
                                <div class="table-responsive">
                                    <table class="table align-middle text-nowrap table-hover">
                                        <thead class="table-primary text-center">
                                            <tr>
                                                <th>Date</th>
                                                <th>Service</th>
                                                <th>Profile ID</th>
                                                <th>Token</th>
                                                <th>Amount</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($history as $data)
                                                <tr>
                                                    <td class="text-center">{{ $data->created_at->format('d M Y') }}</td>
                                                    <td>{{ strtoupper($data->network) }}</td>
                                                    <td>{{ $data->phone_number }}</td>
                                                    <td>
                                                        @php
                                                            $meta = json_decode($data->description, true); // Assuming description might contain JSON or we parse it differently
                                                            // Actually description is string, we might need to extract token from it or use a dedicated column if available.
                                                            // Based on previous implementation, token is in description "PIN: xxxx"
                                                            preg_match('/PIN: (.*)/', $data->description, $matches);
                                                            $token = $matches[1] ?? 'N/A';
                                                        @endphp
                                                        <span class="fw-bold text-dark">{{ $token }}</span>
                                                    </td>
                                                    <td class="text-end">₦{{ number_format($data->amount, 2) }}</td>
                                                    <td class="text-center">
                                                        <span class="badge bg-success-subtle text-success fw-semibold">Successful</span>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                    <div class="d-flex justify-content-center mt-3">
                                        {{ $history->links('vendor.pagination.bootstrap-4') }}
                                    </div>
                                </div>
                            @else
                                <center>
                                    <img src="{{ asset('assets/img/landing/user3.png') }}" width="55%" alt="">
                                </center>
                                <div class="text-center mt-3">
                                    <p class="fw-semibold text-muted fs-15 mb-2">No JAMB pins purchased yet.</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- PIN Confirmation Modal --}}
    <div class="modal fade" id="pinModal" tabindex="-1" aria-labelledby="pinModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 shadow-lg border-0">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-semibold" id="pinModalLabel">
                        <i class="bi bi-shield-lock-fill me-2"></i> Enter Your Transaction PIN
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body text-center py-4">
                    <p class="text-muted mb-3 small">
                        Confirm purchase for <strong><span id="modal-customer-name"></span></strong>.<br>
                        Please enter your <strong>5-digit transaction PIN</strong>.
                    </p>

                    <div class="d-flex justify-content-center">
                        <input type="password" name="pin" id="pinInput" class="form-control text-center fw-bold fs-3 py-3 border-2 border-primary rounded-pill shadow-sm w-50" maxlength="5" inputmode="numeric" placeholder="•••••" required style="letter-spacing: 10px; font-family: 'Courier New', monospace;">
                    </div>
                    <small id="pinError" class="text-danger d-none mt-3 d-block fw-semibold">Incorrect PIN. Please try again.</small>
                </div>

                <div class="modal-footer border-0 justify-content-center pb-4">
                    <button type="button" class="btn btn-light px-4 rounded-pill" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" id="confirmPinBtn" class="btn btn-primary px-4 rounded-pill fw-semibold">
                        <span class="spinner-border spinner-border-sm me-2 d-none" id="pinLoader" role="status" aria-hidden="true"></span>
                        <span id="confirmPinText">Confirm & Proceed</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const verifyBtn = document.getElementById('verify-btn');
            const profileIdInput = document.getElementById('profile_id');
            const serviceSelect = document.getElementById('service_id');
            const verifyStatus = document.getElementById('verify-status');
            const customerInfo = document.getElementById('customer-info');
            const customerNameSpan = document.getElementById('customer-name');
            const modalCustomerName = document.getElementById('modal-customer-name');
            const proceedBtn = document.getElementById('proceed-btn');
            const amountInput = document.getElementById('amountToPay');

            // Fetch price when service changes
            serviceSelect.addEventListener('change', function() {
                const service = this.value;
                if(service) {
                    fetch("{{ route('fetch.bundle.price') }}?id=" + service) // Reusing existing price fetcher if possible, or we can hardcode/fetch specifically
                        .then(res => res.json())
                        .then(price => {
                            // If price fetcher works for variation codes, we need to know the variation code for JAMB.
                            // Usually JAMB UTME is 'jamb-utme' or similar. 
                            // Let's assume the controller passes prices or we fetch them.
                            // For now, we might need a specific route or use the generic one if variation code matches.
                            // Let's try to fetch price via a new route or just rely on verification to return price?
                            // Verification returns commission details, not necessarily full price.
                            // Let's use a dedicated route for JAMB price or generic fetch.
                            // Assuming 'fetch.bundle.price' works with variation code.
                            // We need to know the variation code. 
                            // Let's assume variation code is same as service for now or 'utme' / 'de'.
                        });
                        
                    // For simplicity, let's fetch price via a dedicated simple endpoint or just set it if we passed it to view.
                    // We will handle price fetching in the verification step as well or separate.
                }
            });

            verifyBtn.addEventListener('click', function() {
                const service = serviceSelect.value;
                const profileId = profileIdInput.value;

                if (!service || !profileId) {
                    alert('Please select a service and enter a Profile ID.');
                    return;
                }

                verifyBtn.disabled = true;
                verifyBtn.textContent = 'Verifying...';
                verifyStatus.textContent = '';
                customerInfo.classList.add('d-none');
                proceedBtn.disabled = true;

                fetch("{{ route('verify.jamb') }}", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": "{{ csrf_token() }}"
                    },
                    body: JSON.stringify({ service, profile_id: profileId })
                })
                .then(res => res.json())
                .then(data => {
                    verifyBtn.disabled = false;
                    verifyBtn.textContent = 'Verify';

                    if (data.success) {
                        verifyStatus.className = 'd-block mt-1 fw-bold text-success';
                        verifyStatus.textContent = 'Verification Successful!';
                        
                        customerNameSpan.textContent = data.customer_name;
                        modalCustomerName.textContent = data.customer_name;
                        customerInfo.classList.remove('d-none');
                        
                        // Update amount if returned
                        if(data.amount) {
                            amountInput.value = data.amount;
                        }

                        proceedBtn.disabled = false;
                    } else {
                        verifyStatus.className = 'd-block mt-1 fw-bold text-danger';
                        verifyStatus.textContent = data.message || 'Verification failed.';
                    }
                })
                .catch(err => {
                    console.error(err);
                    verifyBtn.disabled = false;
                    verifyBtn.textContent = 'Verify';
                    verifyStatus.className = 'd-block mt-1 fw-bold text-danger';
                    verifyStatus.textContent = 'Network error. Please try again.';
                });
            });

            // Refresh Plans Logic
            document.getElementById('refresh-plans').addEventListener('click', function() {
                const btn = this;
                const originalText = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Loading...';

                fetch("{{ route('get-variation') }}?type=jamb")
                    .then(res => res.json())
                    .then(data => {
                        if(data.success) {
                            location.reload(); // Reload to show new plans
                        } else {
                            alert('Failed to fetch plans. Please try again.');
                            btn.innerHTML = originalText;
                            btn.disabled = false;
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        alert('Network error.');
                        btn.innerHTML = originalText;
                        btn.disabled = false;
                    });
            });

            // PIN Confirmation Logic (Same as other pages)
            document.getElementById('confirmPinBtn').addEventListener('click', function() {
                const confirmBtn = this;
                const loader = document.getElementById('pinLoader');
                const confirmText = document.getElementById('confirmPinText');
                const pinError = document.getElementById('pinError');
                const pin = document.getElementById('pinInput').value.trim();

                if (!pin) {
                    pinError.textContent = "Please enter your PIN.";
                    pinError.classList.remove('d-none');
                    return;
                }

                confirmBtn.disabled = true;
                loader.classList.remove('d-none');
                confirmText.textContent = "Verifying...";

                fetch("{{ route('user.verify.pin') }}", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": "{{ csrf_token() }}"
                    },
                    body: JSON.stringify({ pin })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.valid) {
                        document.getElementById('buy-jamb-form').submit();
                    } else {
                        pinError.textContent = "Incorrect PIN. Please try again.";
                        pinError.classList.remove('d-none');
                        confirmBtn.disabled = false;
                        loader.classList.add('d-none');
                        confirmText.textContent = "Confirm & Proceed";
                    }
                })
                .catch(err => {
                    pinError.textContent = "Network error. Please try again.";
                    pinError.classList.remove('d-none');
                    confirmBtn.disabled = false;
                    loader.classList.add('d-none');
                    confirmText.textContent = "Confirm & Proceed";
                });
            });
        });
    </script>
</x-app-layout>
