<x-app-layout>
    <title>Hanan Verify - Pay Electricity Bill</title>

    <div class="row">
        <div class="col-xxl-12 col-xl-12">
            <div class="row mt-3">
                <!-- Left Column: Purchase Form -->
                <div class="col-xl-4 mb-3">
                    <div class="card custom-card shadow-sm border-0">
                        <div class="card-header justify-content-between bg-primary text-white rounded-top">
                            <div class="card-title fw-semibold">
                                <i class="bi bi-lightning-charge-fill me-2"></i> Pay Electricity Bill
                            </div>
                        </div>
                        <div class="card-body">

                            <center class="mb-3">
                                <img class="img-fluid" src="{{ asset('assets/img/apps/electricity.jpg') }}" width="35%" onerror="this.src='{{ asset('assets/img/apps/pin.png') }}'">
                            </center>

                            <p class="text-center text-muted mb-4">
                                Select your provider, verify your meter number, and pay your electricity bill instantly.
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

                            <form id="buy-electricity-form" method="POST" action="{{ route('buy.electricity') }}">
                                @csrf
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Select Provider (Disco)</label>
                                    <select name="service_id" id="service_id" class="form-select text-center" required>
                                        <option value="">-- Select Provider --</option>
                                        <option value="ikeja-electric">Ikeja Electric (IKEDC)</option>
                                        <option value="eko-electric">Eko Electric (EKEDC)</option>
                                        <option value="kano-electric">Kano Electric (KEDCO)</option>
                                        <option value="portharcourt-electric">Port Harcourt Electric (PHED)</option>
                                        <option value="jos-electric">Jos Electric (JED)</option>
                                        <option value="ibadan-electric">Ibadan Electric (IBEDC)</option>
                                        <option value="kaduna-electric">Kaduna Electric (KAEDCO)</option>
                                        <option value="abuja-electric">Abuja Electric (AEDC)</option>
                                        <option value="enugu-electric">Enugu Electric (EEDC)</option>
                                        <option value="benin-electric">Benin Electric (BEDC)</option>
                                        <option value="aba-electric-payment">Aba Electric (ABA)</option>
                                        <option value="yola-electric">Yola Electric (YEDC)</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Meter Type</label>
                                    <select name="meter_type" id="meter_type" class="form-select text-center" required>
                                        <option value="">-- Select Type --</option>
                                        <option value="prepaid">Prepaid</option>
                                        <option value="postpaid">Postpaid</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Meter Number</label>
                                    <div class="input-group">
                                        <input type="text" id="meter_number" name="meter_number" class="form-control text-center" placeholder="Enter Meter Number" required>
                                        <button class="btn btn-outline-primary" type="button" id="verify-btn">Verify</button>
                                    </div>
                                    <small id="verify-status" class="d-block mt-1 fw-bold"></small>
                                </div>

                                <div id="customer-info" class="alert alert-info d-none">
                                    <div class="fw-bold">Name: <span id="customer-name" class="text-primary"></span></div>
                                    <div class="small text-muted">Address: <span id="customer-address"></span></div>
                                </div>

                                {{-- Amount --}}
                                <div class="mb-3 text-start">
                                    <label for="amount" class="form-label fw-semibold d-flex justify-content-between">
                                        <span>Amount (₦)</span>
                                        <small class="text-muted">Balance: 
                                            <strong class="text-success">
                                                ₦{{ number_format($wallet->balance ?? 0, 2) }}
                                            </strong>
                                        </small>
                                    </label>
                                    <input type="number" id="amount" name="amount" class="form-control text-center" placeholder="Enter Amount (min 100)" min="100" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Phone Number</label>
                                    <input type="text" id="phone" name="phone" maxlength="11"
                                           class="form-control text-center" placeholder="Enter Phone Number" required>
                                </div>

                                <div class="d-grid mt-4">
                                    <button type="button" class="btn btn-primary btn-lg fw-semibold" id="proceed-btn" disabled
                                        data-bs-toggle="modal" data-bs-target="#pinModal">
                                        Proceed to Pay
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
                                <i class="bi bi-list-task me-2 text-primary"></i> Electricity Payment History
                            </div>
                        </div>
                        <div class="card-body">
                            <p class="text-muted mb-3">Below is your recent electricity bill payment history:</p>

                            @if (isset($history) && !$history->isEmpty())
                                <div class="table-responsive">
                                    <table class="table align-middle text-nowrap table-hover">
                                        <thead class="table-primary text-center">
                                            <tr>
                                                <th>Date</th>
                                                <th>Provider</th>
                                                <th>Meter No.</th>
                                                <th>Token/Ref</th>
                                                <th>Amount</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($history as $data)
                                                <tr>
                                                    <td class="text-center">{{ $data->created_at->format('d M Y') }}</td>
                                                    <td>
                                                        @php
                                                            $metadata = json_decode($data->metadata, true);
                                                            $serviceId = $metadata['service_id'] ?? 'N/A';
                                                            $discoName = strtoupper(str_replace('-', ' ', $serviceId));
                                                        @endphp
                                                        {{ $discoName }}
                                                    </td>
                                                    <td>{{ $metadata['meter_number'] ?? 'N/A' }}</td>
                                                    <td>
                                                        @php
                                                            $token = $metadata['token'] ?? 'N/A';
                                                        @endphp
                                                        <span class="fw-bold text-dark" title="{{ $token }}">{{ Str::limit($token, 20) }}</span>
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
                                    <p class="fw-semibold text-muted fs-15 mb-2">No electricity payments made yet.</p>
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
                        Confirm payment of <strong>₦<span id="modal-amount"></span></strong> for <strong><span id="modal-customer-name"></span></strong>.<br>
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
            const meterInput = document.getElementById('meter_number');
            const serviceSelect = document.getElementById('service_id');
            const typeSelect = document.getElementById('meter_type');
            const verifyStatus = document.getElementById('verify-status');
            const customerInfo = document.getElementById('customer-info');
            const customerNameSpan = document.getElementById('customer-name');
            const customerAddressSpan = document.getElementById('customer-address');
            const modalCustomerName = document.getElementById('modal-customer-name');
            const modalAmount = document.getElementById('modal-amount');
            const proceedBtn = document.getElementById('proceed-btn');
            const amountInput = document.getElementById('amount');

            verifyBtn.addEventListener('click', function() {
                const service = serviceSelect.value;
                const type = typeSelect.value;
                const meter = meterInput.value;

                if (!service || !type || !meter) {
                    alert('Please select a provider, meter type, and enter a meter number.');
                    return;
                }

                verifyBtn.disabled = true;
                verifyBtn.textContent = 'Verifying...';
                verifyStatus.textContent = '';
                customerInfo.classList.add('d-none');
                proceedBtn.disabled = true;

                fetch("{{ route('verify.electricity') }}", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": "{{ csrf_token() }}"
                    },
                    body: JSON.stringify({ service_id: service, meter_type: type, meter_number: meter })
                })
                .then(res => res.json())
                .then(data => {
                    verifyBtn.disabled = false;
                    verifyBtn.textContent = 'Verify';

                    if (data.success) {
                        verifyStatus.className = 'd-block mt-1 fw-bold text-success';
                        verifyStatus.textContent = 'Verification Successful!';
                        
                        customerNameSpan.textContent = data.customer_name;
                        customerAddressSpan.textContent = data.address;
                        modalCustomerName.textContent = data.customer_name;
                        
                        customerInfo.classList.remove('d-none');
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

            // Update modal amount when amount input changes
            amountInput.addEventListener('input', function() {
                modalAmount.textContent = this.value;
            });

            // PIN Confirmation Logic
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
                        document.getElementById('buy-electricity-form').submit();
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
