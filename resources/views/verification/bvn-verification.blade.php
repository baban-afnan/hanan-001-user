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
                                            <tr class="table-light">
                                                <th colspan="2" class="text-center fw-bold">Personal Details</th>
                                            </tr>
                                            <tr>
                                                <th class="w-40 bg-light">BVN Number</th>
                                                <td class="fw-bold text-primary">{{ session('verification')['data']['bvn'] ?? session('verification')['data']['idno'] ?? 'N/A' }}</td>
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
                                                <th class="bg-light">Marital Status</th>
                                                <td>{{ session('verification')['data']['maritalStatus'] ?? 'N/A' }}</td>
                                            </tr>
                                            <tr>
                                                <th class="bg-light">Nationality</th>
                                                <td>{{ session('verification')['data']['nationality'] ?? 'Nigerian' }}</td>
                                            </tr>

                                            <tr class="table-light">
                                                <th colspan="2" class="text-center fw-bold">Contact & Residence</th>
                                            </tr>
                                            <tr>
                                                <th class="bg-light">Primary Phone</th>
                                                <td>{{ session('verification')['data']['phoneNumber'] ?? 'N/A' }}</td>
                                            </tr>
                                            <tr>
                                                <th class="bg-light">Secondary Phone</th>
                                                <td>{{ session('verification')['data']['phoneNumber2'] ?? 'N/A' }}</td>
                                            </tr>
                                            <tr>
                                                <th class="bg-light">Email Address</th>
                                                <td>{{ session('verification')['data']['email'] ?? 'N/A' }}</td>
                                            </tr>
                                            <tr>
                                                <th class="bg-light">Origin</th>
                                                <td>
                                                    {{ session('verification')['data']['lgaOfOrigin'] ?? 'N/A' }}, 
                                                    {{ session('verification')['data']['stateOfOrigin'] ?? 'N/A' }} State
                                                </td>
                                            </tr>
                                            <tr>
                                                <th class="bg-light">Residence</th>
                                                <td>
                                                    {{ session('verification')['data']['lgaOfResidence'] ?? 'N/A' }}, 
                                                    {{ session('verification')['data']['stateOfResidence'] ?? 'N/A' }} State
                                                </td>
                                            </tr>
                                            <tr>
                                                <th class="bg-light">Residential Address</th>
                                                <td>{{ session('verification')['data']['residentialAddress'] ?? 'N/A' }}</td>
                                            </tr>

                                            <tr class="table-light">
                                                <th colspan="2" class="text-center fw-bold">Enrollment Information</th>
                                            </tr>
                                            <tr>
                                                <th class="bg-light">Enrollment Bank</th>
                                                <td>{{ session('verification')['data']['enrollmentBank'] ?? 'N/A' }}</td>
                                            </tr>
                                            <tr>
                                                <th class="bg-light">Enrollment Branch</th>
                                                <td>{{ session('verification')['data']['enrollmentBranch'] ?? 'N/A' }}</td>
                                            </tr>
                                            <tr>
                                                <th class="bg-light">Registration Date</th>
                                                <td>{{ session('verification')['data']['registrationDate'] ?? 'N/A' }}</td>
                                            </tr>
                                            <tr>
                                                <th class="bg-light">Level of Account</th>
                                                <td>{{ session('verification')['data']['levelOfAccount'] ?? 'N/A' }}</td>
                                            </tr>
                                            <tr>
                                                <th class="bg-light">Watchlisted</th>
                                                <td>
                                                    @php
                                                        $watchlisted = session('verification')['data']['watchListed'] ?? 'false';
                                                    @endphp
                                                    @if(strtolower($watchlisted) == 'true')
                                                        <span class="badge bg-danger">YES</span>
                                                    @else
                                                        <span class="badge bg-success">NO</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <hr class="my-4">

                                <h6 class="fw-bold mb-3 text-center text-secondary">Download Slips (Charges Apply)</h6>
                                <div class="d-flex flex-wrap justify-content-center gap-2">
                                    <button onclick="confirmDownload('{{ route('user.standardBVN', session('verification')['data']['bvn'] ?? session('verification')['data']['idno'] ?? '') }}', 'Standard Slip', {{ $standardSlipPrice ?? 0 }})" 
                                        class="btn btn-secondary btn-wave">
                                        <i class="bi bi-file-earmark-text me-1"></i> Standard <br>
                                        <small class="badge bg-dark bg-opacity-25">₦{{ number_format($standardSlipPrice ?? 0, 2) }}</small>
                                    </button>

                                    <button onclick="confirmDownload('{{ route('user.premiumBVN', session('verification')['data']['bvn'] ?? session('verification')['data']['idno'] ?? '') }}', 'Premium Slip', {{ $premiumSlipPrice ?? 0 }})" 
                                        class="btn btn-primary btn-wave">
                                        <i class="bi bi-file-earmark-richtext me-1"></i> Premium <br>
                                        <small class="badge bg-dark bg-opacity-25">₦{{ number_format($premiumSlipPrice ?? 0, 2) }}</small>
                                    </button>

                                    {{-- Changed generic link to button for consistent SweetAlert handling --}}
                                    <button onclick="confirmDownload('{{ route('user.plasticBVN', session('verification')['data']['bvn'] ?? session('verification')['data']['idno'] ?? '') }}', 'Plastic Slip', {{ $plasticSlipPrice ?? 0 }}, true)"
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
                if (!result.isConfirmed) return;

                // Show loading state
                Swal.fire({
                    title: 'Generating Slip...',
                    text: 'Please wait while we process your request. Do not refresh.',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Use background fetch so the page never reloads
                fetch(url, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/pdf, application/json, text/html, */*',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(async (response) => {
                    const contentType = response.headers.get('content-type') || '';

                    if (!response.ok) {
                        let errorMsg = `Download failed (HTTP ${response.status})`;
                        try {
                            if (contentType.includes('application/json')) {
                                const errData = await response.json();
                                errorMsg = errData.message || (errData.errors ? Object.values(errData.errors).flat().join('<br>') : errorMsg);
                            } else {
                                const errText = await response.text();
                                if (errText && errText.length < 300) errorMsg = errText;
                            }
                        } catch (e) {}

                        Swal.fire({
                            title: 'Failed!',
                            html: errorMsg,
                            icon: 'error',
                            confirmButtonColor: '#3085d6'
                        });
                        return;
                    }

                    if (contentType.includes('application/json')) {
                        const jsonData = await response.json();
                        Swal.close();
                        if (jsonData.view) {
                            var newWindow = window.open('', '_blank');
                            if (newWindow) {
                                newWindow.document.write(jsonData.view);
                                newWindow.document.close();
                            } else {
                                Swal.fire('Pop-up Blocked', 'Please allow pop-ups for this site to view and print your slip.', 'warning');
                            }
                        } else if (jsonData.message) {
                            Swal.fire('Notice', jsonData.message, 'info');
                        } else {
                            Swal.fire('Error', 'Failed to generate slip response.', 'error');
                        }
                        return;
                    }

                    // Direct binary download (Plastic Slip PDF)
                    const blob = await response.blob();
                    let filename = `${type.replace(/[^a-zA-Z0-9_-]/g, '_')}.pdf`;
                    const disposition = response.headers.get('content-disposition');
                    if (disposition) {
                        const filenameMatch = disposition.match(/filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/);
                        if (filenameMatch && filenameMatch[1]) {
                            filename = filenameMatch[1].replace(/['"]/g, '').trim();
                        }
                    }

                    const downloadUrl = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.style.display = 'none';
                    a.href = downloadUrl;
                    a.download = filename;
                    document.body.appendChild(a);
                    a.click();
                    setTimeout(() => {
                        document.body.removeChild(a);
                        window.URL.revokeObjectURL(downloadUrl);
                    }, 100);

                    Swal.fire({
                        icon: 'success',
                        title: 'Downloaded!',
                        text: `${type} downloaded successfully without refreshing the page!`,
                        timer: 3000,
                        showConfirmButton: false,
                        toast: true,
                        position: 'top-end'
                    });
                })
                .catch((err) => {
                    Swal.fire({
                        title: 'Network Error',
                        text: 'An error occurred during slip download: ' + (err.message || 'Please check your connection.'),
                        icon: 'error',
                        confirmButtonColor: '#3085d6'
                    });
                });
            });
        }
    </script>

</x-app-layout>
