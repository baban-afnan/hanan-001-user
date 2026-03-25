<x-app-layout>
    <title>Hanan Verify - {{ $title ?? 'Buy Airtime' }}</title>
    
    
    
    @push('styles')
    <style>
        .network-option {
            cursor: pointer;
            padding: 10px;
            border: 2px solid transparent;
            border-radius: 10px;
            transition: all 0.2s ease-in-out;
        }
        .network-option:hover {
            background-color: #f8f9fa;
        }
        .network-option.active {
            border-color: #df6808ff; /* Bootstrap primary color */
            background-color: #e7f1ff;
        }
        .small-note {
            font-size: 0.8rem;
            color: #6c757d;
        }
    </style>
    @endpush

    <div class="row">
        <div class="col-xxl-12 col-xl-12">
            <div class="row mt-3">
              @if(true)
                {{-- Left Column for Airtime Form --}}
                <div class="col-xl-6 mb-3">
                    <div class="card custom-card shadow-sm border-0">
                        <div class="card-header justify-content-between bg-primary text-white rounded-top">
                            <div class="card-title fw-semibold">
                                <i class="bi bi-phone me-2"></i> Buy Airtime
                            </div>
                        </div>

                        <div class="card-body">
                            <!-- Alerts -->
                            <div class="mb-4">
                                @if (session('success'))
                                    <div class="alert alert-success alert-dismissible fade show text-center">{!! session('success') !!}</div>
                                @endif
                                @if (session('error'))
                                    <div class="alert alert-danger alert-dismissible fade show text-center">{{ session('error') }}</div>
                                @endif
                                @if ($errors->any())
                                    <div class="alert alert-danger alert-dismissible fade show">
                                        <ul class="mb-0 ps-3 small">
                                            @foreach ($errors->all() as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                            </div>

                            <!-- Airtime Form -->
                            <form id="buyAirtimeForm" method="POST" action="{{ route('buyairtime') }}">
                                @csrf
                                <input type="hidden" id="selectedNetwork" name="network" value="{{ old('network') }}">

                                <!-- Network Selection -->
                                <div class="network-selection mb-4">
                                    <h6 class="text-center mb-3 fw-semibold">Select Network Provider</h6>
                                    <div class="row text-center g-3">
                                        @php
                                            $networks = [
                                                'mtn' => ['name' => 'MTN', 'img' => 'mtn.jpg'],
                                                'airtel' => ['name' => 'Airtel', 'img' => 'Airtel.png'],
                                                'glo' => ['name' => 'Glo', 'img' => 'glo.jpg'],
                                                'etisalat' => ['name' => 'etisalat', 'img' => '9Mobile.jpg'],
                                            ];
                                        @endphp
                                        @foreach ($networks as $key => $network)
                                            <div class="col-3">
                                                <div class="network-option d-flex flex-column align-items-center" data-network="{{ $key }}" title="{{ $network['name'] }}">
                                                    <img src="{{ asset('assets/img/apps/' . $network['img']) }}" alt="{{ $network['name'] }}" class="rounded-circle mb-1 shadow-sm" style="width: 45px; height: 45px;">
                                                    <div class="small fw-semibold">{{ $network['name'] }}</div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                                <!-- Phone Number -->
                                <div class="mb-3">
                                    <label for="mobileno" class="form-label fw-semibold">Phone Number</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-telephone-fill"></i></span>
                                        <input type="tel" id="mobileno" name="mobileno" value="{{ old('mobileno') }}" class="form-control text-center" maxlength="11" pattern="\d{11}" placeholder="Enter 11-digit phone number" required>
                                    </div>
                                    <div id="networkResult" class="mt-1 small-note text-center fw-bold text-primary"></div>
                                </div>

                                <!-- Amount -->
                                <div class="mb-4">
                                    <label for="amount" class="form-label d-flex justify-content-between align-items-center fw-semibold">
                                        <span>Amount</span>
                                        <small class="text-muted">
                                            Balance:
                                            <strong class="text-success">
                                                ₦{{ number_format($wallet->balance ?? 0, 2) }}
                                            </strong>
                                        </small>
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light fw-bold text-secondary">₦</span>
                                        <input type="number" id="amount" name="amount" value="{{ old('amount') }}" class="form-control form-control-lg text-center" min="50" max="10000" placeholder="e.g., 500" required>
                                    </div>
                                </div>

                                <!-- Amount Suggestions -->
                                <div class="amount-suggestions mb-4">
                                    <p class="text-center text-muted small mb-2">Or select a quick amount</p>
                                    <div class="row g-2">
                                        @php $amounts = [100, 200, 500, 1000, 2000, 5000]; @endphp
                                        @foreach ($amounts as $amt)
                                            <div class="col">
                                                <button type="button" class="btn btn-outline-secondary w-100 amount-btn btn-sm" data-amount="{{ $amt }}">
                                                    ₦{{ number_format($amt) }}
                                                </button>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                                <!-- Submit Button -->
                                <div class="d-grid">
                                    <button type="button" id="buy-airtime" class="btn btn-primary btn-lg fw-semibold">
                                        <i class="bi bi-lightning-charge me-2"></i> Buy Now
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Right Column --}}
                @include('utilities.advert')
            </div>
        </div>
    </div>

    {{-- PIN Confirmation Modal --}}
    <div class="modal fade" id="pinModal" tabindex="-1" aria-labelledby="pinModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 shadow-lg border-0">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title text-white fw-semibold" id="pinModalLabel">
                        <i class="bi bi-shield-lock-fill me-2"></i> Confirm Transaction PIN
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body text-center py-4">
                    <p class="text-muted mb-3 small">
                        Please enter your <strong>5-digit PIN</strong> to confirm this transaction.
                    </p>
                    <div class="d-flex justify-content-center">
                        <input type="password" name="pin" id="pinInput" class="form-control text-center fw-bold fs-3 py-3 border-2 border-primary rounded-pill shadow-sm w-50" maxlength="5" inputmode="numeric" placeholder="•••••" required style="letter-spacing: 10px; font-family: 'Courier New', monospace;">
                    </div>
                    <small id="pinError" class="text-danger d-none mt-2 d-block fw-semibold">Incorrect PIN. Please try again.</small>
                </div>

                <div class="modal-footer border-0 justify-content-center pb-4">
                    <button type="button" class="btn btn-light px-4 rounded-pill" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" id="confirmPinBtn" class="btn btn-primary px-4 rounded-pill fw-semibold">
                        Confirm & Proceed
                    </button>
                </div>
            </div>
        </div>
    </div>
    </div>
  <div class="container-fluid px-4 mt-4">


     @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const networkOptions = document.querySelectorAll('.network-option');
                const selectedNetworkInput = document.getElementById('selectedNetwork');
                const amountInput = document.getElementById('amount');
                const amountButtons = document.querySelectorAll('.amount-btn');
                const phoneInput = document.getElementById('mobileno');
                const networkResultDiv = document.getElementById('networkResult');
                const buyButton = document.getElementById('buy-airtime');
                const confirmButton = document.getElementById('confirmPinBtn');

                // --- Network selection ---
                networkOptions.forEach(option => {
                    option.addEventListener('click', function () {
                        networkOptions.forEach(opt => opt.classList.remove('active'));
                        this.classList.add('active');
                        selectedNetworkInput.value = this.dataset.network;
                    });
                });

                // --- Amount suggestion ---
                amountButtons.forEach(button => {
                    button.addEventListener('click', function () {
                        amountInput.value = this.dataset.amount;
                    });
                });

                // --- Network detection ---
                const networkPrefixes = {
                    'mtn': ['0803','0806','0703','0706','0810','0813','0814','0816','0903','0906','0913','0916','07025','07026','0704','09065'],
                    'glo': ['0805','0807','0705','0811','0815','0905','0915'],
                    'airtel': ['0802','0808','0701','0708','0812','0901','0902','0904','0907','0912'],
                    'etisalat': ['0809','0817','0818','0908','0909']
                };

                phoneInput.addEventListener('input', function () {
                    const phoneNumber = this.value;
                    networkResultDiv.textContent = '';
                    if (phoneNumber.length >= 4) {
                        const prefix = phoneNumber.substring(0, 4);
                        for (const network in networkPrefixes) {
                            if (networkPrefixes[network].includes(prefix)) {
                                networkResultDiv.textContent = `Looks like a ${network.toUpperCase()} number.`;
                                document.querySelector(`.network-option[data-network="${network}"]`)?.click();
                                break;
                            }
                        }
                    }
                });

                // --- Handle Buy Click ---
                buyButton.addEventListener('click', function () {
                    const pinModal = new bootstrap.Modal(document.getElementById('pinModal'));
                    pinModal.show();
                });

                // --- Confirm PIN & Prevent Double Click ---
                confirmButton.addEventListener('click', function () {
                    const pin = document.getElementById('pinInput').value;
                    const pinError = document.getElementById('pinError');

                    this.disabled = true;
                    this.innerHTML = '<i class="bi bi-arrow-repeat spinner-border spinner-border-sm"></i> Verifying...';

                    fetch("{{ route('user.verify.pin') }}", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            "X-CSRF-TOKEN": "{{ csrf_token() }}"
                        },
                        body: JSON.stringify({ pin })
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.valid) {
                            document.getElementById('buyAirtimeForm').submit();
                        } else {
                            pinError.classList.remove('d-none');
                            this.disabled = false;
                            this.innerHTML = 'Confirm & Proceed';
                        }
                    })
                    .catch(() => {
                        alert("Network error, please try again.");
                        this.disabled = false;
                        this.innerHTML = 'Confirm & Proceed';
                    });
                });
            });
        </script>
    @endpush

</x-app-layout>
