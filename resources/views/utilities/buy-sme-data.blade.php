<x-app-layout>
    <title>Hanan Verify - SME Data Plans</title>

    <div class="row">
        <div class="col-xxl-12 col-xl-12">
            <div class="row mt-3">
                <div class="col-xl-6 mb-3">
                    <div class="card custom-card shadow-sm border-0">
                        <div class="card-header justify-content-between bg-success text-white rounded-top">
                            <div class="card-title fw-semibold">
                                <i class="ti ti-world me-2"></i> SME Data Service
                            </div>
                        </div>

                        <div class="card-body">
                            <center class="mb-3">
                                <img src="{{ asset('assets/img/apps/network_providers.png') }}"
                                     class="img-fluid mb-3 rounded-2"
                                     style="width: 45%; min-width: 120px;" alt="Network Providers">
                            </center>

                            <p class="text-center text-muted mb-4">
                                Select network, plan type, and your desired data bundle.
                            </p>

                            {{-- Flash Messages --}}
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

                            {{-- Buy SME Data Form --}}
                            <form id="buySmeDataForm" method="POST" action="{{ route('user.buy-sme-data.submit') }}">
                                @csrf

                                {{-- Network Selection --}}
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Select Network</label>
                                    <select name="network" id="service_id" class="form-select text-center" required>
                                        <option value="">Choose Network</option>
                                        @foreach ($sme_datas as $network)
                                            <option value="{{ $network->network }}">{{ $network->network }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                {{-- Data Type subselection (Gifting, SME, Corporate etc) --}}
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Data Type</label>
                                    <select name="type" id="type" class="form-select text-center" required>
                                        <option value="">Select Type</option>
                                    </select>
                                </div>

                                {{-- Data Plan --}}
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Data Plan</label>
                                    <select name="plan" id="plan" class="form-select text-center" required>
                                        <option value="">Select Plan</option>
                                    </select>
                                </div>

                                {{-- Amount --}}
                                <div class="mb-3 text-start">
                                    <label for="amountToPay" class="form-label fw-semibold d-flex justify-content-between">
                                        <span>Amount to Pay</span>
                                        <small class="text-muted">Balance: 
                                            <strong class="text-success">
                                                ₦{{ number_format($wallet->balance ?? 0, 2) }}
                                            </strong>
                                        </small>
                                    </label>
                                    <input type="text" id="amountToPay" name="amount" readonly class="form-control text-center bg-light fw-bold" placeholder="₦ 0.00" />
                                </div>

                                {{-- Phone Number --}}
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Recipient Phone Number</label>
                                    <input type="text" id="mobileno" name="mobileno"
                                           class="form-control text-center"
                                           placeholder="08012345678"
                                           maxlength="11" required>
                                </div>

                                {{-- Submit --}}
                                <div class="d-grid mt-4">
                                    <button type="button" class="btn btn-primary btn-lg fw-semibold"
                                            data-bs-toggle="modal" data-bs-target="#pinModal">
                                        Purchase Data
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                {{-- Right Column --}}
                @include('utilities.advert')
            </div>
        </div>
    </div>

    {{-- PIN Confirmation Modal --}}
    <div class="modal fade" id="pinModal" tabindex="-1" aria-labelledby="pinModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 shadow-lg border-0">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title fw-semibold" id="pinModalLabel">
                        <i class="bi bi-shield-lock-fill me-2"></i> Confirm Transaction
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body text-center py-4">
                    <p class="text-muted mb-3 small">
                        Please enter your <strong>4-digit transaction PIN</strong> to authorize this purchase.
                    </p>

                    <div class="d-flex justify-content-center">
                        <input 
                            type="password" 
                            name="pin" 
                            id="pinInput" 
                            class="form-control text-center fw-bold fs-3 py-3 border-2 border-success rounded-pill shadow-sm w-50" 
                            maxlength="4" 
                            inputmode="numeric" 
                            placeholder="••••"
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
                    <button type="button" id="confirmPinBtn" class="btn btn-success px-4 rounded-pill fw-semibold">
                        <span class="spinner-border spinner-border-sm me-2 d-none" id="pinLoader" role="status" aria-hidden="true"></span>
                        <span id="confirmPinText">Confirm Purchase</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
<div class="row mt-3">

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    $(document).ready(function () {
        $("#service_id").change(function () {
            let service_id = $(this).val();
            if(!service_id) return;
            
            $.ajax({
                type: "get",
                url: "{{ route('user.sme.fetch.type') }}",
                data: { id: service_id },
                dataType: "json",
                success: function (response) {
                    var len = response.length;
                    $("#type").empty();
                    $("#type").append("<option value=''>Data Type</option>");

                    for (var i = 0; i < len; i++) {
                        var plan_type = response[i]["plan_type"];
                        $("#type").append("<option value='" + plan_type + "'>" + plan_type + "</option>");
                    }
                    $("#plan").empty().append("<option value=''>Select Plan</option>");
                    $("#amountToPay").val("");
                },
                error: function (data) {
                    console.error("Error fetching data types", data);
                },
            });
        });

        $("#type").change(function () {
            let service_id = $("#service_id").val();
            let type = $(this).val();
            if(!service_id || !type) return;

            $.ajax({
                type: "get",
                url: "{{ route('user.sme.fetch.plan') }}",
                data: { id: service_id, type: type },
                dataType: "json",
                success: function (response) {
                    var len = response.length;
                    $("#plan").empty();
                    $("#plan").append("<option value=''>Data Plan</option>");

                    for (var i = 0; i < len; i++) {
                        var plan_text = response[i]["size"] + " " + response[i]["plan_type"] + " (" + response[i]["amount"] + ") " + response[i]["validity"];
                        var id = response[i]["data_id"];
                        $("#plan").append("<option value='" + id + "'>" + plan_text + "</option>");
                    }
                    $("#amountToPay").val("");
                },
                error: function (data) {
                    console.error("Error fetching data plans", data);
                },
            });
        });

        $("#plan").change(function () {
            let plan_id = $(this).val();
            if(!plan_id) {
                $("#amountToPay").val("");
                return;
            }

            $.ajax({
                type: "get",
                url: "{{ route('user.sme.fetch.price') }}",
                data: { id: plan_id },
                dataType: "json",
                success: function (response) {
                    $("#amountToPay").val("₦ " + response);
                },
                error: function (data) {
                    console.error("Error fetching price", data);
                },
            });
        });

        // PIN Confirmation Logic
        $('#confirmPinBtn').on('click', function() {
            const confirmBtn = $(this);
            const loader = $('#pinLoader');
            const confirmText = $('#confirmPinText');
            const pinError = $('#pinError');
            const pin = $('#pinInput').val().trim();

            if (!pin) {
                pinError.text("Please enter your PIN.").removeClass('d-none');
                return;
            }

            confirmBtn.prop('disabled', true);
            loader.removeClass('d-none');
            confirmText.text("Verifying...");

            $.ajax({
                type: "POST",
                url: "{{ route('user.verify.pin') }}",
                data: { 
                    pin: pin,
                    _token: "{{ csrf_token() }}"
                },
                success: function(data) {
                    if (data.valid) {
                        $('#buySmeDataForm').submit();
                    } else {
                        pinError.text("Incorrect PIN. Please try again.").removeClass('d-none');
                        confirmBtn.prop('disabled', false);
                        loader.addClass('d-none');
                        confirmText.text("Confirm Purchase");
                    }
                },
                error: function() {
                    pinError.text("Network error. Please try again.").removeClass('d-none');
                    confirmBtn.prop('disabled', false);
                    loader.addClass('d-none');
                    confirmText.text("Confirm Purchase");
                }
            });
        });
    });
    </script>

</x-app-layout>
