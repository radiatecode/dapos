@extends('admin::app.layouts.app')

@section('title', 'Create plan')

@section('page_heading')
    Create plan
@endsection

@section('breadcrumbs')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.plans.index') }}">Plans</a></li>
        <li class="breadcrumb-item active">Create</li>
    </ol>
@endsection

@section('content')
    <div class="admin-form-page">
        <form method="POST" id="create-plan-form" action="{{ route('admin.plans.store') }}" novalidate
            data-parsley-validate="" data-parsley-excluded="input[type=hidden], [disabled]" autocomplete="off">
            @csrf

            <div class="row">
                <div class="col-12">
                    <x-admin::boot-card title="Create plan">
                        <x-slot:header>
                            <p class="admin-card-hint mb-0">Set pricing, then assign boolean features and limits.</p>
                        </x-slot:header>

                        @include('admin::app.plans._fields')

                        <x-slot:footer>
                            <a href="{{ route('admin.plans.index') }}" class="btn admin-btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Back
                            </a>
                            <x-admin::button type="submit" class="btn-primary" text="Create" icon="fas fa-save" />
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

            $.ajaxSubmitOnValidated('create-plan-form', {
                loader: 'btn-loader',
                errorType: 'inline',
                redirectTo: function() {
                    window.location.href = @json(route('admin.plans.index'));
                },
            });
        });
    </script>
@endpush
