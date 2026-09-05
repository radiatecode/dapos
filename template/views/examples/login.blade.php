@extends('ui.layouts.guest')

@section('title', 'Sign in')

@section('content')
    <div class="guest-grid">
        <section class="guest-hero">
            <div>
                <div class="d-flex align-items-center gap-3 mb-5">
                    <span class="admin-brand-mark">A</span>
                    <div>
                        <div class="text-uppercase small" style="letter-spacing: .16em; color: #fbbf24;">Admin template</div>
                        <strong>Control plane</strong>
                    </div>
                </div>
                <h1 class="display-5 fw-bold mb-3" style="letter-spacing: -0.05em;">A calm cockpit for everyday operations.</h1>
                <p class="lead" style="color: #cbd5e1; max-width: 34rem;">
                    Sign in to manage records, people, and settings from one polished workspace.
                </p>
            </div>
        </section>

        <section class="guest-panel">
            <div class="guest-form">
                <div class="mb-4">
                    <div class="admin-page-kicker">Welcome back</div>
                    <h2 class="h3 fw-bold mb-1">Sign in</h2>
                    <p class="text-secondary mb-0">Use your administrator credentials.</p>
                </div>

                <form method="POST" action="#" novalidate>
                    @csrf
                    <div class="mb-3">
                        <label for="email" class="form-label fw-semibold">Email</label>
                        <input id="email" class="form-control admin-input" type="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label fw-semibold">Password</label>
                        <input id="password" class="form-control admin-input" type="password" name="password" required>
                    </div>
                    <button type="submit" class="btn btn-admin-accent w-100">Continue to dashboard</button>
                </form>
            </div>
        </section>
    </div>
@endsection
