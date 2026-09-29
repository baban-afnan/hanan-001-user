@extends('layouts.dashboard')

@section('title', 'NIN Verification')

@section('content')
<div class="container-fluid px-0">
    <!-- Header Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card bg-primary bg-gradient border-0 shadow-sm rounded-3">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h3 class="text-white fw-bold mb-1">NIN Verification</h3>
                            <p class="text-white text-opacity-75 mb-0">Verify NIN instantly and download slips.</p>
                        </div>
                        <div class="bg-white bg-opacity-25 p-3 rounded-circle">
                            <i class="mdi mdi-fingerprint text-white fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- NIN Verification Form -->
        <div class="col-xl-5 mb-4">
            <div class="card shadow-sm border-0 rounded-3 h-100">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="mb-0 fw-bold text-dark"><i class="mdi mdi-shield-search me-2 text-primary"></i>Verify NIN</h5>
                </div>

                <div class="card-body p-4">
                    {{-- Alerts --}}
                    @if (session('status') && session('message'))
                        <div class="alert alert-{{ session('status') === 'success' ? 'success' : 'danger' }} alert-dismissible fade show border-0 shadow-sm" role="alert">
                            <i class="mdi mdi-{{ session('status') === 'success' ? 'check-circle' : 'alert-circle' }} me-2"></i>
                            {{ session('message') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert">
                            <ul class="mb-0 small ps-3">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('user.nin.verification.store') }}">
                        @csrf
                        <div class="mb-4">
                            <label class="form-label fw-semibold text-muted small text-uppercase">NIN Number (11 Digits)</label>
                            <div class="input-group input-group-lg border rounded-3 overflow-hidden">
                                <span class="input-group-text bg-light border-0"><i class="mdi mdi-numeric text-primary"></i></span>
                                <input class="form-control border-0 bg-light text-center fw-bold" name="number_nin" type="text"
                                    placeholder="00000000000" maxlength="11" minlength="11" pattern="[0-9]{11}"
                                    required value="{{ old('number_nin') }}">
                            </div>
                            <small class="text-muted mt-2 d-block text-center italic">Verify identity records from the national database.</small>
                        </div>

                        <div class="card bg-light border-0 mb-4 rounded-3">
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="text-muted small">Service Charge:</span>
                                    <span class="fw-bold text-dark">₦{{ number_format($verificationPrice ?? 0, 2) }}</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted small">Wallet Balance:</span>
                                    <span class="fw-bold text-success">₦{{ number_format($wallet->balance ?? 0, 2) }}</span>
                                </div>
                            </div>
                        </div>

                        <button class="btn btn-primary w-100 btn-lg fw-bold py-3 rounded-3 shadow-sm hover-up" type="submit">
                            <i class="mdi mdi-magnify me-2"></i> VERIFY NOW
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Verification Result -->
        <div class="col-xl-7 mb-4">
            <div class="card shadow-sm border-0 rounded-3 h-100">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="mb-0 fw-bold text-dark"><i class="mdi mdi-account-card-details me-2 text-primary"></i>Verification Result</h5>
                </div>

                <div class="card-body p-4">
                    @php
                        $verificationData = session('verification')['data'] ?? [];
                    @endphp
                    @include('partials.verification_result_card', [
                        'downloadRoutes' => [
                            'basic'    => !empty($verificationData['nin']) ? route('user.nin.verification.basic', $verificationData['nin']) : '#',
                            'regular'  => !empty($verificationData['nin']) ? route('user.nin.verification.regular', $verificationData['nin']) : '#',
                            'standard' => !empty($verificationData['nin']) ? route('user.nin.verification.standard', $verificationData['nin']) : '#',
                            'premium'  => !empty($verificationData['nin']) ? route('user.nin.verification.premium', $verificationData['nin']) : '#',
                            'vnin'     => !empty($verificationData['nin']) ? route('user.nin.verification.vnin', $verificationData['nin']) : '#',
                        ]
                    ])
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .hover-up { transition: transform 0.2s ease; }
    .hover-up:hover { transform: translateY(-3px); }
    .bg-gradient { background: linear-gradient(45deg, #0db4bd 0%, #089ea5 100%) !important; }
    .alert-soft-success { border-left: 4px solid #2e7d32 !important; }
    .table-hover tbody tr:hover { background-color: rgba(13, 180, 189, 0.05); }
    .uppercase { text-transform: uppercase; }
</style>
@endpush

@push('scripts')

<script>
    @if (session('status') === 'success')
        window.addEventListener('load', () => {
            const speak = () => {
                const message = "NIN verification is successful. The identification number is valid.";
                const utterance = new SpeechSynthesisUtterance(message);
                const voices = window.speechSynthesis.getVoices();
                const femaleVoice = voices.find(voice => 
                    ['female', 'samantha', 'victoria', 'google uk english female'].some(v => voice.name.toLowerCase().includes(v))
                );
                if (femaleVoice) utterance.voice = femaleVoice;
                utterance.rate = 1.0;
                utterance.pitch = 1.1;
                window.speechSynthesis.speak(utterance);
                return true;
            };
            if (!speak()) window.speechSynthesis.onvoiceschanged = speak;
        });
    @endif
</script>
@endpush
