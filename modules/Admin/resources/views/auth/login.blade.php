@extends('admin::layouts.guest')

@section('title', 'Sign in')

@section('content')
    <div class="guest-grid">
        <section class="guest-hero">
            <div>
                <div class="d-flex align-items-center gap-3 mb-5">
                    <span class="admin-brand-mark">DA</span>
                    <div>
                        <div class="text-uppercase small" style="letter-spacing: .16em; color: #fbbf24;">Radiate POS</div>
                        <strong>Provider control plane</strong>
                    </div>
                </div>
                <h1 class="display-5 fw-bold mb-3" style="letter-spacing: -0.05em;">Operate every tenant from one beautiful
                    cockpit.</h1>
                <p class="lead" style="color: #cbd5e1; max-width: 34rem;">
                    Sign in to manage tenants, plans, billing and system configuration. Point of sale users cannot access
                    this area.
                </p>
            </div>
            <div class="d-flex gap-4 text-white-50 small">
                <div><i class="bi bi-shield-lock me-1"></i> Role gated</div>
                <div><i class="bi bi-buildings me-1"></i> Multi-tenant</div>
                <div><i class="bi bi-lightning me-1"></i> Session auth</div>
            </div>
        </section>

        <section class="guest-panel">
            <div class="guest-form">
                <div class="mb-4">
                    <div class="admin-page-kicker">Welcome back</div>
                    <h2 class="h3 fw-bold mb-1">Sign in to admin</h2>
                    <p class="text-secondary mb-0">Use your provider administrator credentials.</p>
                </div>

                @if ($errors->any())
                    <x-admin::alert type="danger">
                        {{ $errors->first() }}
                    </x-admin::alert>
                @endif

                <form method="POST" action="{{ url('/admin/login') }}" novalidate>
                    @csrf

                    <div class="mb-3">
                        <label for="email" class="form-label fw-semibold">Email</label>
                        <input id="email" class="form-control admin-input @error('email') is-invalid @enderror"
                            type="email" name="email" value="{{ old('email') }}" required autofocus
                            autocomplete="username">
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label fw-semibold">Password</label>
                        <input id="password" class="form-control admin-input @error('password') is-invalid @enderror"
                            type="password" name="password" required autocomplete="current-password">
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" name="remember" id="remember">
                        <label class="form-check-label" for="remember">Remember me</label>
                    </div>

                    <button type="submit" class="btn btn-admin-accent w-100">
                        Continue to dashboard
                    </button>
                </form>
            </div>
        </section>
    </div>
@endsection
