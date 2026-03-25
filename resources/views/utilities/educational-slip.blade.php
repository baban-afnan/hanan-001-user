<x-app-layout>
    <title>Hanan Verify - Educational Pin Receipt</title>

    <div class="page-body">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-10 col-md-8 mx-auto">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <a href="{{ route('education') }}" class="text-decoration-none text-muted">
                            <i class="bi bi-arrow-left me-1"></i> Back to Education
                        </a>
                        <button onclick="window.print()" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-printer me-1"></i> Print Receipt
                        </button>
                    </div>

                    <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
                        <div class="card-header bg-primary text-white p-4">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="mb-1 fw-bold">Transaction Receipt</h5>
                                    <p class="mb-0 opacity-75 small">Ref: {{ $transaction->transaction_ref }}</p>
                                </div>
                                <div class="text-end">
                                    <h2 class="mb-0 fw-bold">₦{{ number_format($transaction->amount, 2) }}</h2>
                                    <span class="badge bg-white text-primary fw-semibold rounded-pill px-3">Successful</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card-body p-4 bg-white">
                            @php
                                $meta = json_decode($transaction->metadata, true) ?? [];
                                $apiResponse = $meta['api_response'] ?? [];
                                $txContent = $apiResponse['content']['transactions'] ?? [];
                                $commission = $txContent['commission'] ?? 0;
                                
                                $purchasedCode = $meta['purchased_code'] ?? '';
                                $pins = [];
                                if ($purchasedCode) {
                                    $pins = explode('||', $purchasedCode);
                                }
                            @endphp

                            <!-- Payer Info -->
                            <div class="row g-4 mb-4 border-bottom pb-4">
                                <div class="col-md-6">
                                    <h6 class="text-muted text-uppercase small fw-bold mb-3">Payer Information</h6>
                                    <div class="d-flex flex-column gap-2">
                                        <div>
                                            <small class="text-muted d-block">Name</small>
                                            <span class="fw-medium text-dark">{{ $meta['payer_name'] ?? 'N/A' }}</span>
                                        </div>
                                        <div>
                                            <small class="text-muted d-block">Email</small>
                                            <span class="fw-medium text-dark">{{ $meta['payer_email'] ?? 'N/A' }}</span>
                                        </div>
                                        <div>
                                            <small class="text-muted d-block">Phone</small>
                                            <span class="fw-medium text-dark">{{ $meta['payer_phone'] ?? $meta['phone'] ?? 'N/A' }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="text-muted text-uppercase small fw-bold mb-3">Transaction Details</h6>
                                    <div class="d-flex flex-column gap-2">
                                        <div>
                                            <small class="text-muted d-block">Service</small>
                                            <span class="fw-medium text-dark text-uppercase">{{ $meta['service'] ?? 'Educational Pin' }}</span>
                                        </div>
                                        <div>
                                            <small class="text-muted d-block">Date</small>
                                            <span class="fw-medium text-dark">{{ $transaction->created_at->format('M d, Y h:i A') }}</span>
                                        </div>
                                        <div>
                                            <small class="text-muted d-block">Commission Earned</small>
                                            <span class="fw-bold text-success">+₦{{ number_format($commission, 2) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Purchased Pins -->
                            <div class="mb-4">
                                <h6 class="text-muted text-uppercase small fw-bold mb-3">Purchased Tokens / PINs</h6>
                                <div class="bg-light rounded-3 p-3 border border-dashed text-break">
                                    @if(count($pins) > 0)
                                        @foreach($pins as $index => $pin)
                                            <div class="mb-2 pb-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                                                <span class="badge bg-primary mb-1">Item {{ $index + 1 }}</span>
                                                <p class="mb-0 font-monospace fw-bold text-dark fs-6">{{ trim($pin) }}</p>
                                            </div>
                                        @endforeach
                                    @else
                                        <p class="mb-0 text-muted">No PINs details found.</p>
                                    @endif
                                </div>
                            </div>

                            <!-- Remaining Balance -->
                            <div class="alert alert-info border-0 d-flex justify-content-between align-items-center mb-0">
                                <span class="fw-medium"><i class="bi bi-wallet2 me-2"></i>Wallet Balance After Transaction</span>
                                <span class="fw-bold fs-5">₦{{ number_format($wallet->balance ?? 0, 2) }}</span>
                            </div>

                        </div>
                        
                        <div class="card-footer bg-light p-3 text-center">
                            <p class="text-muted small mb-0">Thank you for using Arewa Smart services.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
