<x-app-layout>
    <title>Hanan Verify - {{ $title ?? 'Verify BVN' }}</title>
    <div class="page-body">
        <div class="container-fluid">
            <div class="page-title mb-3">
                <div class="row">
                    <div class="col-sm-6 col-12">
                        <h3 class="fw-bold text-primary">BVN Verification</h3>
                        <p class="text-muted small mb-0">Verify BVN instantly and download slips.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="container-fluid">
            <div class="row mt-3">
                <!-- BVN Verification Form -->
                <div class="col-xl-6 mb-4">
                    <div class="card shadow-sm border-0 rounded-3">
                        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                            <h5 class="mb-0 fw-bold"><i class="bi bi-shield-check me-2"></i>Verify BVN</h5>
                            <span class="badge bg-light text-primary fw-semibold">Instant</span>
                        </div>

                        <div class="card-body">
                            <div class="text-center mb-3">
                                <p class="text-muted small mb-0">
                                    Enter the 11-digit BVN number below to verify.
                                </p>
                            </div>

                            {{-- Alerts --}}
                            @if (session('status') && session('message'))
                                <div class="alert alert-{{ session('status') === 'success' ? 'success' : 'danger' }} alert-dismissible fade show" role="alert">
                                    {{ session('message') }}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            @endif

                            @if ($errors->any())
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <ul class="mb-0 small text-start">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            @endif

                            <form method="POST" action="{{ route('user.bvn.verification.store') }}">
                                @csrf
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label class="form-label fw-semibold">BVN Number <span class="text-danger">*</span></label>
                                        <input class="form-control text-center form-control-lg" name="bvn" type="text"
                                            placeholder="Enter 11 Digit BVN" maxlength="11" minlength="11" pattern="[0-9]{11}"
                                            required value="{{ old('bvn') }}">
                                    </div>

                                    <div class="col-12">
                                        <div class="alert alert-info py-2 mb-0 d-flex justify-content-between align-items-center">
                                            <span class="fw-semibold">Service Fee:</span>
                                            <strong class="fs-15">₦{{ number_format($verificationPrice ?? 0, 2) }}</strong>
                                        </div>
                                        <div class="text-end mt-1">
                                            <small class="text-muted">
                                                Wallet Balance: <strong class="text-success">₦{{ number_format($wallet->balance ?? 0, 2) }}</strong>
                                            </small>
                                        </div>
                                    </div>

                                    <div class="col-12 d-grid mt-3">
                                        <button class="btn btn-primary btn-lg fw-semibold" type="submit">
                                            <i class="bi bi-search me-2"></i> Verify Now
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Verification Info -->
                <div class="col-xl-6">
                    <div class="card shadow-sm border-0 rounded-3">
                        <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                            <h5 class="mb-0 fw-bold"><i class="bi bi-person-lines-fill me-2"></i>Verification Result</h5>
                        </div>

                        <div class="card-body">
                            @if (session('verification'))
                                <div class="alert alert-success text-center mb-3">
                                    <i class="bi bi-check-circle-fill me-2"></i> <strong>Verification Successful!</strong>
                                </div>

                                <div class="text-center mb-4">
                                    <div class="d-inline-block p-1 border rounded bg-white shadow-sm">
                                        @if (!empty(session('verification')['data']['photo']))
                                            <img src="data:image/jpeg;base64,{{ session('verification')['data']['photo'] }}"
                                                alt="ID Photo" class="img-fluid rounded"
                                                style="max-height:180px; min-width: 150px; object-fit: cover;">
                                        @else
                                            <img src="{{ asset('assets/images/corrupt.jpg') }}" alt="No Image"
                                                class="img-fluid rounded" style="max-height:180px;">
                                        @endif
                                    </div>
                                    <div class="mt-2 fw-bold text-muted small">PASSPORT PHOTOGRAPH</div>
                                </div>

                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped align-middle">
                                        <tbody>
                                            <tr>
                                                <th class="w-40 bg-light">BVN Number</th>
                                                <td class="fw-bold text-primary">{{ session('verification')['data']['bvn'] }}</td>
                                            </tr>
                                            <tr>
                                                <th class="bg-light">First Name</th>
                                                <td>{{ session('verification')['data']['firstName'] }}</td>
                                            </tr>
                                            <tr>
                                                <th class="bg-light">Last Name</th>
                                                <td>{{ session('verification')['data']['lastName'] }}</td>
                                            </tr>
                                            <tr>
                                                <th class="bg-light">Middle Name</th>
                                                <td>{{ session('verification')['data']['middleName'] ?? 'N/A' }}</td>
                                            </tr>
                                            <tr>
                                                <th class="bg-light">Date of Birth</th>
                                                <td>
                                                    {{ !empty(session('verification')['data']['birthday'])
                                                        ? \Carbon\Carbon::parse(session('verification')['data']['birthday'])->format('d M, Y')
                                                        : 'N/A' }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <th class="bg-light">Gender</th>
                                                <td>{{ ucfirst(session('verification')['data']['gender'] ?? 'N/A') }}</td>
                                            </tr>
                                            <tr>
                                                <th class="bg-light">Phone</th>
                                                <td>{{ session('verification')['data']['phoneNumber'] ?? 'N/A' }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <hr class="my-4">

                                <h6 class="fw-bold mb-3 text-center text-secondary">Download Slips (Charges Apply)</h6>
                                <div class="d-flex flex-wrap justify-content-center gap-2">
                                    <button onclick="confirmDownload('{{ route('user.standardBVN', session('verification')['data']['bvn']) }}', 'Standard Slip', {{ $standardSlipPrice ?? 0 }})" 
                                        class="btn btn-secondary btn-wave">
                                        <i class="bi bi-file-earmark-text me-1"></i> Standard <br>
                                        <small class="badge bg-dark bg-opacity-25">₦{{ number_format($standardSlipPrice ?? 0, 2) }}</small>
                                    </button>

                                    <button onclick="confirmDownload('{{ route('user.premiumBVN', session('verification')['data']['bvn']) }}', 'Premium Slip', {{ $premiumSlipPrice ?? 0 }})" 
                                        class="btn btn-primary btn-wave">
                                        <i class="bi bi-file-earmark-richtext me-1"></i> Premium <br>
                                        <small class="badge bg-dark bg-opacity-25">₦{{ number_format($premiumSlipPrice ?? 0, 2) }}</small>
                                    </button>

                                    {{-- Changed generic link to button for consistent SweetAlert handling --}}
                                    <button onclick="confirmDownload('{{ route('user.plasticBVN', session('verification')['data']['bvn']) }}', 'Plastic Slip', {{ $plasticSlipPrice ?? 0 }}, true)"
                                       class="btn btn-info btn-wave text-white">
                                        <i class="bi bi-credit-card-2-front me-1"></i> Plastic <br>
                                        <small class="badge bg-dark bg-opacity-25">₦{{ number_format($plasticSlipPrice ?? 0, 2) }}</small>
                                    </button>
                                </div>

                            @else
                                <div class="text-center py-5">
                                    <img src="{{ asset('assets/img/apps/thankyou.png') }}" width="120" alt="Search Icon" class="opacity-50 mb-3">
                                    <h6 class="text-muted">Verification results will appear here.</h6>
                                    <p class="small text-muted">Enter a BVN number on the left to get started.</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <!-- SweetAlert2 CDN -->


    <!-- Success Voice & Slip Download Script -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // AI Voice Notification for Success
        @if (session('status') === 'success')
            window.addEventListener('load', () => {
                const speak = () => {
                    const message = "wow verification is successful Id number is valid";
                    const utterance = new SpeechSynthesisUtterance(message);
                    
                    const voices = window.speechSynthesis.getVoices();
                    if (voices.length === 0) return false;

                    const femaleVoice = voices.find(voice => 
                        voice.name.toLowerCase().includes('female') || 
                        voice.name.toLowerCase().includes('google uk english female') ||
                        voice.name.toLowerCase().includes('samantha') ||
                        voice.name.toLowerCase().includes('victoria')
                    );
                    
                    if (femaleVoice) utterance.voice = femaleVoice;
                    utterance.rate = 1.0;
                    utterance.pitch = 1.1;
                    window.speechSynthesis.speak(utterance);
                    return true;
                };

                if (!speak()) {
                    window.speechSynthesis.onvoiceschanged = speak;
                }
            });
        @endif

        function confirmDownload(url, type, price, isDirectDownload = false) {
            Swal.fire({
                title: 'Confirm Download',
                text: `You will be charged ₦${price.toLocaleString()} for the ${type}. Do you want to proceed?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, Proceed!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    
                    if(isDirectDownload) {
                        // For plastic slip or direct downloads
                        window.location.href = url;
                        return;
                    }

                    // Show loading state
                    Swal.fire({
                        title: 'Generating Slip...',
                        text: 'Please wait while we process your request.',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    // Use AJAX to fetch the view/json
                    $.ajax({
                        url: url,
                        type: 'GET',
                        success: function(response) {
                            Swal.close(); // Close loading
                            
                            if (response.view) {
                                // Open the view in a new window/tab for printing
                                var newWindow = window.open('', '_blank');
                                newWindow.document.write(response.view);
                                newWindow.document.close();
                            } else {
                                Swal.fire('Error', 'Failed to generate slip response.', 'error');
                            }
                        },
                        error: function(xhr) {
                            Swal.close(); // Close loading
                            var msg = 'An error occurred.';
                            if (xhr.responseJSON && xhr.responseJSON.errors) {
                                msg = Object.values(xhr.responseJSON.errors).join('\n');
                            } else if (xhr.responseJSON && xhr.responseJSON.message) {
                                msg = xhr.responseJSON.message;
                            }
                            Swal.fire('Failed!', msg, 'error');
                        }
                    });
                }
            });
        }
    </script>

</x-app-layout>
