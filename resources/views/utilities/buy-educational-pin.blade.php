<x-app-layout>
    <title>Hanan Verify - Buy Educational Pin</title>

    <!-- Start::row-1 -->
    <div class="row">
        <div class="col-xxl-12 col-xl-12">
            <div class="row mt-3">
                <!-- Left Column: Purchase Form -->
                <div class="col-xl-4 mb-3">
                    <div class="card custom-card shadow-sm border-0">
                        <div class="card-header justify-content-between bg-primary text-white rounded-top">
                            <div class="card-title fw-semibold">
                                <i class="bi bi-credit-card me-2"></i> Buy Educational Pin
                            </div>
                        </div>
                        <div class="card-body">

                            <center class="mb-3">
                                <img class="img-fluid" src="{{ asset('assets/img/apps/pin.png') }}" width="35%">
                            </center>

                            <p class="text-center text-muted mb-4">
                                Select your educational pin service, choose the type, enter your phone number,
                                and continue to complete your purchase securely.
                            </p>

                            {{-- Alert Messages --}}
                            @if (session('success'))
                                <div class="alert alert-success alert-dismissible fade show text-center" role="alert">
                                    {{ session('success') }}
                                </div>
                            @endif

                            @if (session('error'))
                                <div class="alert alert-danger alert-dismissible fade show text-center" role="alert">
                                    {{ session('error') }}
                                </div>
                            @endif

                            @if ($errors->any())
                                <div class="alert alert-danger alert-dismissible fade show">
                                    <ul class="mb-0">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <form id="buy-pin" method="POST" action="{{ route('buypin') }}">
                                @csrf
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Select Service</label>
                                    <select name="service" id="service_id" class="form-select text-center" required>
                                        <option value="">-- Select Service --</option>
                                        <option value="waec">WAEC Result Checker</option>
                                        <option value="waec-registration">WAEC Registration</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Select Type</label>
                                    <select name="type" id="type" class="form-select text-center" required>
                                        <option value="">-- Choose Type --</option>
                                        @foreach ($pins as $p)
                                            <option value="{{ $p->variation_code }}" data-amount="{{ $p->variation_amount }}">
                                                {{ strtoupper($p->name ?? $p->variation_code) }}
                                            </option>
                                        @endforeach
                                    </select>
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
                                                <input type="text" id="amountToPay" name="amount" readonly class="form-control text-center" />
                                            </div>

                                      <div class="mb-3">
                                    <label class="form-label fw-semibold">Phone Number</label>
                                    <input type="text" id="mobileno" name="mobileno" maxlength="11"
                                           class="form-control text-center" placeholder="Enter 11-digit number" required>
                                </div>

                                <div class="d-grid mt-4">
                                <button type="button" class="btn btn-primary btn-lg fw-semibold"
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
                                <i class="bi bi-list-task me-2 text-primary"></i> Purchase History
                            </div>
                        </div>
                        <div class="card-body">
                            <p class="text-muted mb-3">Below is your recent educational pin purchase history:</p>

                            @if (isset($history) && !$history->isEmpty())
                                <div class="table-responsive">
                                    <table class="table align-middle text-nowrap table-hover">
                                        <thead class="table-primary text-center">
                                            <tr>
                                                <th>Date</th>
                                                <th>Service</th>
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
                                                    <td>
                                                        @php
                                                            // Extract token from description if not in a separate column
                                                            // Assuming format "Educational pin purchase (WAEC) - PIN: xxxx"
                                                            preg_match('/PIN: (.*)/', $data->description, $matches);
                                                            $token = $matches[1] ?? 'N/A';
                                                        @endphp
                                                        <span class="fw-bold text-dark font-monospace">{{ $token }}</span>
                                                    </td>
                                                    <td class="text-end">₦{{ number_format($data->amount, 2) }}</td>
                                                    <td class="text-center">
                                                        <span class="badge bg-success-subtle text-success fw-semibold">Successful</span>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>

                                    <!-- Pagination -->
                                    <div class="d-flex justify-content-center mt-3">
                                        {{ $history->links('vendor.pagination.bootstrap-4') }}
                                    </div>
                                </div>
                            @else
                                <center>
                                    <img src="{{ asset('assets/img/landing/user3.png') }}" width="55%" alt="">
                                </center>
                              <div class="text-center mt-3">
                                 <p class="fw-semibold text-muted fs-15 mb-2">
                                    No pins purchased yet. Start your educational journey today!
                                 </p>
                                 <p class="text-primary mb-2">
                                    💡 Get instant access to your results and registrations
                                 </p>
                                 <p class="text-success mb-2">
                                    🎓 Secure your academic future with just a few clicks
                                 </p>
                                 <p class="text-info mb-2">
                                    ⚡ Fast, reliable, and available 24/7
                                 </p>
                                 <p class="text-dark">
                                    Buy your first educational pin now and take the first step towards success!
                                 </p>
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
                    For your security, please confirm your <strong>5-digit transaction PIN</strong> before proceeding.
                </p>

                <div class="d-flex justify-content-center">
                    <input 
                        type="password" 
                        name="pin" 
                        id="pinInput" 
                        class="form-control text-center fw-bold fs-3 py-3 border-2 border-primary rounded-pill shadow-sm w-50" 
                        maxlength="5" 
                        inputmode="numeric" 
                        placeholder="•••••"
                        required
                        style="letter-spacing: 10px; font-family: 'Courier New', monospace;"
                    >
                </div>

                <small id="pinError" class="text-danger d-none mt-3 d-block fw-semibold">
                    Incorrect PIN. Please try again.
                </small>
            </div>

            <div class="modal-footer border-0 justify-content-center pb-4">
                <button type="button" class="btn btn-light px-4 rounded-pill" data-bs-dismiss="modal">
                    Cancel
                </button>

                {{-- Main action button --}}
                <button type="button" id="confirmPinBtn" class="btn btn-primary px-4 rounded-pill fw-semibold">
                    <span class="spinner-border spinner-border-sm me-2 d-none" id="pinLoader" role="status" aria-hidden="true"></span>
                    <span id="confirmPinText">Confirm & Proceed</span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('confirmPinBtn').addEventListener('click', function() {
    const confirmBtn = this;
    const loader = document.getElementById('pinLoader');
    const confirmText = document.getElementById('confirmPinText');
    const pinError = document.getElementById('pinError');
    const pin = document.getElementById('pinInput').value.trim();

    // If PIN field is empty, stop
    if (!pin) {
        pinError.textContent = "Please enter your PIN.";
        pinError.classList.remove('d-none');
        return;
    }

    // Disable the button & show loader
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
            // PIN is correct → submit the main form
            document.getElementById('buy-pin').submit();
        } else {
            // Incorrect PIN → show error, reset button
            pinError.textContent = "Incorrect PIN. Please try again.";
            pinError.classList.remove('d-none');
            confirmBtn.disabled = false;
            loader.classList.add('d-none');
            confirmText.textContent = "Confirm & Proceed";
        }
    })
    .catch(err => {
        console.error("PIN check failed:", err);
        pinError.textContent = "Network error. Please try again.";
        pinError.classList.remove('d-none');
        confirmBtn.disabled = false;
        loader.classList.add('d-none');
        confirmText.textContent = "Confirm & Proceed";
    });
});
</script>

</x-app-layout>
