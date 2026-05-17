@extends('layouts.auth')

@section('title', 'Login')

@section('content')
    <div class="content-wrapper d-flex align-items-center auth">
        <div class="row flex-grow">
            <div class="col-lg-4 col-md-6 col-sm-10 mx-auto">
                <div class="auth-form-light text-start p-5 shadow-lg rounded-4 bg-white">
                    <div class="brand-logo text-center mb-4">
                        <img src="{{ asset('assets/images/logo.png') }}" alt="logo" style="max-width: 60px; height: auto;">
                    </div>
                    <div class="text-center mb-4">
                        <h4 class="fw-bold">Welcome back!</h4>
                        <h6 class="fw-light text-muted">Happy to see you again!</h6>
                    </div>

                    @include('common.message')

                    <form method="POST" action="{{ route('auth.login') }}" class="pt-3 needs-validation" novalidate>
                        @csrf

                        <!-- Username -->
                        <div class="form-group mb-3">
                            <label for="email" class="form-label">Username</label>
                            <div class="input-group">
                                <span class="input-group-text bg-transparent border-end-0">
                                    <i class="mdi mdi-account-outline text-primary"></i>
                                </span>
                                <input type="text"
                                    class="form-control form-control-lg border-start-0 @error('email') is-invalid @enderror"
                                    id="email" name="email" placeholder="Username" value="{{ old('email') }}"
                                    required autofocus>
                            </div>
                            @error('email')
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

                        <div class="my-3 d-flex justify-content-between align-items-center">
                            <div class="form-check">
                                <label class="form-check-label text-muted">
                                    <input type="checkbox" class="form-check-input" id="remember" name="remember"
                                        {{ old('remember') ? 'checked' : '' }}>
                                    <i class="input-helper"></i>
                                    Keep me signed in
                                </label>
                            </div>
                            <a href="{{ route('auth.password.request') }}" class="auth-link text-primary text-decoration-none">Forgot password?</a>
                        </div>

                        <!-- Submit Button -->
                        <div class="my-4 d-grid">
                            <button type="submit" class="btn btn-primary btn-lg fw-bold auth-form-btn">
                                LOGIN
                            </button>
                        </div>

                        <div class="text-center mt-4 fw-light">
                            Don't have an account? <a href="{{ route('auth.register') }}" class="text-primary fw-bold text-decoration-none">Create One</a>
                        </div>
                    </form>
                    <p class="text-muted text-center mt-5 small">&copy; {{ date('Y') }} All rights reserved.</p>
                </div>
            </div>
        </div>
    </div>

@endsection
