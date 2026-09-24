@extends('admin::app.layouts.app')

@section('title', 'Edit tenant')

@section('page_heading')
    Edit tenant
@endsection

@section('breadcrumbs')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.tenants.index') }}">Tenants</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.tenants.show', $tenant) }}">{{ $tenant->name }}</a></li>
        <li class="breadcrumb-item active">Edit</li>
    </ol>
@endsection

@section('content')
    <div class="admin-form-page">
        <form method="POST" id="edit-tenant-form" action="{{ route('admin.tenants.update', $tenant) }}"
            enctype="multipart/form-data" novalidate data-parsley-validate=""
            data-parsley-excluded="input[type=hidden], [disabled]" autocomplete="off">
            @csrf
            @method('PUT')

            <div class="row">
                <div class="col-12">
                    <x-admin::boot-card title="Edit {{ $tenant->name }}">
                        <x-slot:header>
                            <p class="admin-card-hint mb-0">Update the business profile, contact, and billing details.</p>
                        </x-slot:header>

                        @include('admin::app.tenants._fields', ['tenant' => $tenant])

                        <x-slot:footer>
                            <a href="{{ route('admin.tenants.show', $tenant) }}" class="btn admin-btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Back
                            </a>
                            <x-admin::button type="submit" class="btn-primary" text="Update" icon="fas fa-save" />
                        </x-slot:footer>
                    </x-admin::boot-card>
                </div>
            </div>
        </form>
    </div>
@endsection

@push('js')
    <script src="{{ asset('vendor/admin/js/submit.on.validated.js') }}?v={{ filemtime(public_path('vendor/admin/js/submit.on.validated.js')) }}"></script>
    <script>
        $(function() {
            initTabbedFormValidation('#edit-tenant-form');

            $.ajaxSubmitOnValidated('edit-tenant-form', {
                loader: 'btn-loader',
                errorType: 'inline',
                redirectTo: function() {
                    window.location.href = @json(route('admin.tenants.show', $tenant));
                },
            });
        });
    </script>
@endpush
