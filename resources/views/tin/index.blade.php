<x-app-layout>
    <title>Hanan Verify - {{ $title ?? 'TIN Verification' }}</title>
    
    <div class="page-body">
        <div class="container-fluid">
            <div class="page-title mb-4">
                <div class="row">
                    <div class="col-sm-6 col-12">
                        <h3 class="fw-bold text-primary">TAX ID Verification (TIN)</h3>
                        <p class="text-muted small mb-0">Register for Individual or Corporate TIN. Validate details first, then download your slip/certificate.</p>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Left Column: Verification Form -->
                <div class="col-lg-6 col-xl-6 mb-4">
                    <div class="card shadow-sm border-0 rounded-3 h-100">
                        <div class="card-header bg-white border-bottom py-3">
                            <h5 class="mb-0 fw-bold text-primary"><i class="bi bi-file-earmark-person me-2"></i>Verification Details</h5>
                        </div>

                        <div class="card-body p-4">
                            {{-- Alerts --}}
                            @if (session('status'))
                                <div class="alert alert-{{ session('status') === 'success' ? 'success' : 'danger' }} alert-dismissible fade show">
                                    {{ session('message') }}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            @endif
                            
                            @if (session('error'))
                                <div class="alert alert-danger alert-dismissible fade show">
                                    {{ session('error') }}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            @endif

                            <form action="{{ route('user.tin.validate') }}" method="POST" id="validationForm">
                                @csrf
                                
                                {{-- Service Type Selection --}}
                                <div class="mb-4">
                                    <label class="form-label fw-bold small text-uppercase text-muted">Service Type <span class="text-danger">*</span></label>
                                    <select class="form-select form-select-lg" name="field_code" id="service_type" required>
                                        @foreach($fields as $field)
                                            @php
                                                // Determine type
                                                $isCorporate = $field->field_code === '801';
                                                $type = $isCorporate ? 'corporate' : 'individual';
                                                
                                                // Calculate Price for User Role
                                                $priceObj = $field->prices->where('user_type', Auth::user()->role)->first();
                                                $price = $priceObj ? $priceObj->price : $field->base_price;
                                            @endphp
                                            <option value="{{ $field->field_code }}" 
                                                    data-type="{{ $type }}" 
                                                    data-price="{{ $price }}" 
                                                    data-description="{{ $field->field_name }}"
                                                    {{ (old('field_code', $fields->first()->field_code) == $field->field_code) ? 'selected' : '' }}>
                                                {{ $field->field_name }} - ₦{{ number_format($price, 2) }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <input type="hidden" name="type" id="hidden_type" value="{{ old('type', 'individual') }}">
                                </div>

                                {{-- Input Fields Container --}}
                                <div class="p-4 bg-light rounded-3 border mb-4">
                                    {{-- Individual Inputs --}}
                                    <div id="individual-inputs">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="form-label fw-semibold">NIN <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" name="nin" id="nin" 
                                                       placeholder="11-digit NIN" maxlength="11" pattern="\d{11}" title="Please enter exactly 11 digits"
                                                       value="{{ old('nin') }}" required>
                                            </div>
                                            
                                            <div class="col-md-6">
                                                <label class="form-label fw-semibold">Date of Birth <span class="text-danger">*</span></label>
                                                <input type="date" class="form-control" name="date_of_birth" 
                                                       id="dob" value="{{ old('date_of_birth') }}" required>
                                            </div>
                                            <div class="col-md-12 mt-3">
                                                <div class="alert alert-info py-2 mb-0">
                                                    <small><i class="bi bi-info-circle me-1"></i> Optional fields below will be auto-filled from NIN verification</small>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">First Name <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" name="first_name" 
                                                       id="first_name" placeholder="First Name" 
                                                       value="{{ old('first_name') }}" required>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">Middle Name</label>
                                                <input type="text" class="form-control" name="middle_name" 
                                                       id="middle_name" placeholder="Middle Name" 
                                                       value="{{ old('middle_name') }}">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">Surname <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" name="last_name" 
                                                       id="last_name" placeholder="Surname" 
                                                       value="{{ old('last_name') }}" required>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Corporate Inputs --}}
                                    <div id="corporate-inputs" class="d-none">
                                        <div class="row g-3">
                                            <div class="col-md-12">
                                                <label class="form-label fw-semibold">Organization Type <span class="text-danger">*</span></label>
                                                <select class="form-select" name="org_type" id="org_type" required>
                                                    <option value="" disabled selected>Select Organization Type</option>
                                                    <option value="1" {{ old('org_type') == '1' ? 'selected' : '' }}>Business Name</option>
                                                    <option value="2" {{ old('org_type') == '2' ? 'selected' : '' }}>Company</option>
                                                    <option value="3" {{ old('org_type') == '3' ? 'selected' : '' }}>Incorporated Trustee</option>
                                                    <option value="4" {{ old('org_type') == '4' ? 'selected' : '' }}>Limited Partnership</option>
                                                    <option value="5" {{ old('org_type') == '5' ? 'selected' : '' }}>Limited Liability Partnership</option>
                                                </select>
                                            </div>
                                            <div class="col-md-12">
                                                <label class="form-label fw-semibold">RC / BN Number <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" name="rc_number" 
                                                       id="rc_number" placeholder="Enter RC Number (e.g. 8891227)" 
                                                       value="{{ old('rc_number') }}" required>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Service Fee & Balance --}}
                                <div class="row g-3 mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Service Fee</label>
                                        <div class="alert alert-info py-2 mb-0 text-center">
                                            <strong id="field-price">₦0.00</strong>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Wallet Balance</label>
                                        <div class="alert alert-success py-2 mb-0 text-center">
                                            <strong>₦{{ number_format($wallet->balance ?? 0, 2) }}</strong>
                                        </div>
                                    </div>
                                </div>
                                
                                {{-- Submit Button --}}
                                <div class="mt-4 pt-3 border-top">
                                    <button type="submit" class="btn btn-primary w-100 py-3 fw-bold shadow-sm" id="btn-validate">
                                        <i class="bi bi-search me-2"></i> <span id="btn-text">Validate Information</span>
                                        <span id="btn-spinner" class="spinner-border spinner-border-sm ms-2 d-none"></span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Verification Result -->
                <div class="col-lg-6 col-xl-6">
                    <div class="card shadow-sm border-0 rounded-3 h-100">
                        <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                            <h5 class="mb-0 fw-bold"><i class="bi bi-person-lines-fill me-2"></i>Verification Result</h5>
                        </div>

                        <div class="card-body">
                            @if (session('verification'))
                                <div class="alert alert-success text-center mb-3">
                                    <i class="bi bi-check-circle-fill me-2"></i> <strong>Verification Successful!</strong>
                                </div>

                              

                                {{-- Verification Details --}}
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped align-middle">
                                        <tbody>
                                            @if(session('verification')['type'] == 'individual')
                                                <tr>
                                                    <th class="w-40 bg-light">NIN Number</th>
                                                    <td class="fw-bold text-primary">{{ session('verification')['data']['nin'] ?? session('verification')['data']['tin'] ?? 'N/A' }}</td>
                                                </tr>

                                                  <tr>
                                                    <th class="w-40 bg-light">Tax ID</th>
                                                    <td class="fw-bold text-primary">{{ session('verification')['data']['tax_id'] ?? session('verification')['data']['tin'] ?? 'N/A' }}</td>
                                                </tr>

                                                <tr>
                                                    <th class="bg-light">First Name</th>
                                                    <td>{{ session('verification')['data']['firstName'] ?? 'N/A' }}</td>
                                                </tr>
                                                <tr>
                                                    <th class="bg-light">Last Name</th>
                                                    <td>{{ session('verification')['data']['surname'] ?? session('verification')['data']['lastName'] ?? 'N/A' }}</td>
                                                </tr>
                                                <tr>
                                                    <th class="bg-light">Middle Name</th>
                                                    <td>{{ session('verification')['data']['middleName'] ?? 'N/A' }}</td>
                                                </tr>
                                                <tr>
                                                    <th class="bg-light">Date of Birth</th>
                                                    <td>
                                                        {{ !empty(session('verification')['data']['birthDate'])
                                                            ? session('verification')['data']['birthDate']
                                                            : (!empty(session('verification')['data']['dateOfBirth']) ? session('verification')['data']['dateOfBirth'] : 'N/A') }}
                                                    </td>
                                                </tr>
                                            @else
                                                <tr>
                                                    <th class="w-40 bg-light">Organization</th>
                                                    <td class="fw-bold text-primary">{{ session('verification')['data']['company_name'] ?? 'N/A' }}</td>
                                                </tr>
                                                <tr>
                                                    <th class="bg-light">RC Number</th>
                                                    <td>{{ session('verification')['data']['rc'] ?? 'N/A' }}</td>
                                                </tr>
                                                <tr>
                                                    <th class="bg-light">Tax ID</th>
                                                    <td>{{ session('verification')['data']['tax_id'] ?? session('verification')['data']['tin'] ?? 'N/A'  }}</td>
                                                </tr>
                                            @endif
                                            <tr>
                                                <th class="bg-light">Phone</th>
                                                <td>{{ session('verification')['data']['telephoneNo'] ?? 'N/A' }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <hr class="my-4">

                                {{-- Download Options --}}
                                <h6 class="fw-bold mb-3 text-center text-secondary">Download Slips (Charges Apply)</h6>
                                <div class="d-flex flex-wrap justify-content-center gap-2">
                                    @if(session('verification')['type'] == 'individual')
                                        {{-- Open (inline) button --}}
                                       

                                        {{-- Download button --}}
                                        <form action="{{ route('user.tin.download') }}" method="POST" class="d-inline" onsubmit="handleDownload(event, this)" data-price="{{ number_format($downloadPrices['individual'] ?? 0, 2) }}" data-type="Individual Slip">
                                            @csrf
                                            <input type="hidden" name="type" value="individual">
                                            <input type="hidden" name="transaction_ref" value="{{ session('verification')['transaction_ref'] }}">
                                            <input type="hidden" name="action" value="download">

                                            <button type="submit" class="btn btn-secondary btn-wave">
                                                <i class="bi bi-file-earmark-text me-1"></i> Download Slip <br>
                                                <small class="badge bg-dark bg-opacity-25">Standard • ₦{{ number_format($downloadPrices['individual'] ?? 0, 2) }}</small>
                                            </button>
                                        </form>
                                    @endif

                                    @if(session('verification')['type'] == 'corporate')
                                        {{-- Download button --}}
                                        <form action="{{ route('user.tin.download') }}" method="POST" class="d-inline" onsubmit="handleDownload(event, this)" data-price="{{ number_format($downloadPrices['corporate'] ?? 0, 2) }}" data-type="Corporate Certificate">
                                            @csrf
                                            <input type="hidden" name="type" value="corporate">
                                            <input type="hidden" name="transaction_ref" value="{{ session('verification')['transaction_ref'] }}">
                                            <input type="hidden" name="action" value="download">

                                            <button type="submit" class="btn btn-primary btn-wave">
                                                <i class="bi bi-file-earmark-richtext me-1"></i> Download Certificate <br>
                                                <small class="badge bg-dark bg-opacity-25">Premium • ₦{{ number_format($downloadPrices['corporate'] ?? 0, 2) }}</small>
                                            </button>
                                        </form>
                                    @endif
                                </div>

                            @else
                                {{-- Empty State --}}
                                <div class="text-center py-5">
                                    <img src="{{ asset('assets/img/apps/thankyou.png') }}" width="120" alt="Search Icon" class="opacity-50 mb-3">
                                    <h6 class="text-muted">Verification results will appear here.</h6>
                                    <p class="small text-muted">Enter a NIN number or RC Number on the left to get started.</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // AI Voice Notification for Success
            @if (session('status') === 'success')
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
            @endif

            const form = document.getElementById('validationForm');
            const typeSelect = document.getElementById('service_type');
            const hiddenType = document.getElementById('hidden_type');
            const indInputs = document.getElementById('individual-inputs');
            const corpInputs = document.getElementById('corporate-inputs');
            const serviceFee = document.getElementById('field-price');
            
            const btnValidate = document.getElementById('btn-validate');
            const btnText = document.getElementById('btn-text');
            const btnSpinner = document.getElementById('btn-spinner');

            function updateForm() {
                const selected = typeSelect.options[typeSelect.selectedIndex];
                const type = selected.getAttribute('data-type');
                const price = selected.getAttribute('data-price');
                
                // Update hidden type
                hiddenType.value = type;
                
                // Toggle input sections
                if(type === 'corporate') {
                    indInputs.classList.add('d-none');
                    corpInputs.classList.remove('d-none');
                    // Toggle required attributes for browser validation
                    toggleRequired(indInputs, false);
                    toggleRequired(corpInputs, true);
                } else {
                    indInputs.classList.remove('d-none');
                    corpInputs.classList.add('d-none');
                    toggleRequired(indInputs, true);
                    toggleRequired(corpInputs, false);
                }
                
                // Update price display
                if(serviceFee) {
                    const formattedPrice = '₦' + parseFloat(price).toLocaleString('en-NG', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                    serviceFee.textContent = formattedPrice;
                }
            }
            
            function toggleRequired(container, isRequired) {
                const inputs = container.querySelectorAll('input, select');
                inputs.forEach(input => {
                    // Only toggle required if it's the main required fields
                    if(input.id === 'nin' || input.id === 'dob' || input.id === 'rc_number' || input.id === 'org_type' || input.id === 'first_name' || input.id === 'last_name') {
                        if(isRequired) input.setAttribute('required', 'required');
                        else input.removeAttribute('required');
                    }
                });
            }

            // Initialize on load
            updateForm();
            
            // Update on change
            typeSelect.addEventListener('change', updateForm);
            
            // Handle Submit Loading State
            form.addEventListener('submit', function() {
                if(form.checkValidity()) {
                    btnValidate.disabled = true;
                    btnText.textContent = 'Validating...';
                    btnSpinner.classList.remove('d-none');
                }
            });

            // SweetAlert Download Confirmation
            window.handleDownload = function(e, form) {
                e.preventDefault();
                
                const price = form.getAttribute('data-price') || '0.00';
                const type = form.getAttribute('data-type') || 'Slip';

                Swal.fire({
                    title: 'Confirm Download',
                    text: `You will be charged ₦${price} for the ${type}. Do you want to proceed?`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, Download!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Submit the form
                        form.submit();
                    }
                });
            }
        });
    </script>
    <style>
        .hover-scale { transition: transform 0.2s; }
        .hover-scale:hover { transform: translateY(-2px); }
        .avatar { width: 80px; height: 80px; display: flex; align-items: center; justify-content: center; }
        .card { transition: box-shadow 0.3s ease; }
        .card:hover { box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important; }
    </style>
</x-app-layout>