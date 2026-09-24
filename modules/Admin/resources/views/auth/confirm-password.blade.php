@extends('admin::layouts.guest')

@section('title', 'Confirm password')

@section('content')
    <div class="guest-panel min-vh-100">
        <div class="guest-form">
            <div class="mb-4">
                <div class="admin-page-kicker">Security check</div>
                <h1 class="h3 fw-bold">Confirm your password</h1>
                <p class="text-secondary">This is a protected area of the provider admin panel.</p>
            </div>

            @if ($errors->any())
                <x-admin::alert type="danger">
                    {{ $errors->first() }}
                </x-admin::alert>
            @endif

            <form method="POST" action="{{ route('password.confirm.store') }}">
                @csrf
                <div class="mb-3">
                    <label for="password" class="form-label fw-semibold">Password</label>
                    <input id="password" type="password" name="password" class="form-control admin-input" required autocomplete="current-password">
                </div>
                <button type="submit" class="btn btn-admin-accent w-100">Confirm</button>
            </form>
        </div>
    </div>
@endsection
