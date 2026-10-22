@extends('admin::app.layouts.app')

@section('title', 'Subscription features')

@section('page_heading')
    Subscription features
@endsection

@section('breadcrumbs')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.subscriptions.index') }}">Subscriptions</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.subscriptions.show', $subscription) }}">#{{ $subscription->id }}</a></li>
        <li class="breadcrumb-item active">Features</li>
    </ol>
@endsection

@section('content')
    <div class="admin-form-page">
        <form method="POST" id="subscription-features-form"
            action="{{ route('admin.subscriptions.features.update', $subscription) }}" novalidate
            data-parsley-validate="" data-parsley-excluded="input[type=hidden], [disabled]" autocomplete="off">
            @csrf
            @method('PUT')

            <div class="row">
                <div class="col-12">
                    <x-admin::boot-card title="Features for {{ $subscription->tenant?->name ?? 'this subscription' }}">
                        <x-slot:header>
                            <p class="admin-card-hint mb-0">
                                {{ $subscription->plan?->name }} · changes here apply only to subscription #{{ $subscription->id }}.
                            </p>
                        </x-slot:header>

                        @include('admin::app.plans._feature_editor')

                        <x-slot:footer>
                            <a href="{{ route('admin.subscriptions.show', $subscription) }}" class="btn admin-btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Back
                            </a>
                            <x-admin::button type="submit" class="btn-primary" text="Update features" icon="fas fa-save" />
                        </x-slot:footer>
                    </x-admin::boot-card>
                </div>
            </div>
        </form>
    </div>
@endsection

@push('js')
    <script src="{{ asset('vendor/admin/js/submit.on.validated.js') }}?v={{ filemtime(public_path('vendor/admin/js/submit.on.validated.js')) }}"></script>
    <script src="{{ asset('vendor/admin/js/plan-feature-editor.js') }}?v={{ filemtime(public_path('vendor/admin/js/plan-feature-editor.js')) }}"></script>
    <script>
        $(function() {
            bindPlanFeatureEditor();

            $.ajaxSubmitOnValidated('subscription-features-form', {
                loader: 'btn-loader',
                errorType: 'inline',
                redirectTo: function() {
                    window.location.href = @json(route('admin.subscriptions.show', $subscription));
                },
            });
        });
    </script>
@endpush
