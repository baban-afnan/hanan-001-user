@extends('layouts.auth')

@section('title', 'Register')
@section('content')
    <div class="content-wrapper d-flex align-items-center auth">
        <div class="row flex-grow">
            <div class="col-lg-5 col-md-8 col-sm-10 mx-auto">
                <div class="auth-form-light text-start p-5 shadow-lg rounded-4 bg-white">
                    <div class="brand-logo text-center mb-4">
                        <img src="{{ asset('assets/images/logo.png') }}" alt="logo" style="max-width: 60px; height: auto;">
                    </div>
                    <div class="text-center mb-4">
                        <h4 class="fw-bold">New here?</h4>
                        <h6 class="fw-light text-muted">Join us today! It takes only a few steps</h6>
                    </div>

                    @include('common.message')

                    <form method="POST" action="{{ route('auth.register') }}" class="pt-3 needs-validation" novalidate>
                        @csrf

                        <!-- Email -->
                        <div class="form-group mb-3">
                            <label for="email" class="form-label">Email</label>
                            <div class="input-group">
                                <span class="input-group-text bg-transparent border-end-0">
                                    <i class="mdi mdi-email-outline text-primary"></i>
                                </span>
                                <input type="email"
                                    class="form-control form-control-lg border-start-0 @error('email') is-invalid @enderror"
                                    id="email" name="email" placeholder="Email" value="{{ old('email') }}" required>
                            </div>
                            @error('email')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Referral Code -->
                        <div class="form-group mb-3">
                            <label for="referral_code" class="form-label">Referral Code (Optional)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-transparent border-end-0">
                                    <i class="mdi mdi-tag-outline text-primary"></i>
                                </span>
                                <input type="text"
                                    class="form-control form-control-lg border-start-0 @error('referral_code') is-invalid @enderror"
                                    id="referral_code" name="referral_code" maxlength="6"
                                    placeholder="Referral Code (if any)" value="{{ old('referral_code') }}">
                            </div>
                            @error('referral_code')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Password -->
                        <div class="form-group mb-3">
                            <label for="password" class="form-label">Password</label>
                            <div class="input-group">
                                <span class="input-group-text bg-transparent border-end-0">
                                    <i class="mdi mdi-lock-outline text-primary"></i>
                                </span>
                                <input type="password"
                                    class="form-control form-control-lg border-start-0 @error('password') is-invalid @enderror"
                                    id="password" name="password" placeholder="Password" required>
                            </div>
                            @error('password')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Confirm Password -->
                        <div class="form-group mb-4">
                            <label for="password_confirmation" class="form-label">Confirm Password</label>
                            <div class="input-group">
                                <span class="input-group-text bg-transparent border-end-0">
                                    <i class="mdi mdi-lock-outline text-primary"></i>
                                </span>
                                <input type="password" class="form-control form-control-lg border-start-0"
                                    id="password_confirmation" name="password_confirmation" placeholder="Confirm Password"
                                    required>
                            </div>
                        </div>

                        <div class="mb-4">
                            <div class="form-check">
                                <label class="form-check-label text-muted">
                                    <input type="checkbox" name="terms" id="terms" value="1"
                                        class="form-check-input @error('terms') is-invalid @enderror" {{ old('terms') ? 'checked' : '' }}>
                                    <i class="input-helper"></i>
                                    I agree to all <a href="#" class="text-primary text-decoration-none fw-bold">Terms & Conditions</a>
                                </label>
                            </div>
                            @error('terms')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Submit Button -->
                        <div class="mt-3 d-grid">
                            <button type="submit" class="btn btn-primary btn-lg fw-bold auth-form-btn">
                                SIGN UP
                            </button>
                        </div>

                        <div class="text-center mt-4 fw-light">
                            Already have an account? <a href="{{ route('auth.login') }}" class="text-primary fw-bold text-decoration-none">Login</a>
                        </div>
                    </form>
                    <p class="text-muted text-center mt-5 small">&copy; {{ date('Y') }} All rights reserved.</p>
                </div>
            </div>
        </div>
    </div>
@endsection
