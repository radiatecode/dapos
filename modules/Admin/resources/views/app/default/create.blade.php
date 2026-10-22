@extends('admin::app.layouts.app')

@section('title', 'Tenant Default Data')

@section('page_heading')
    <i class="fa fa-book-open-reader"></i> Tenant Default Data
@endsection

@section('breadcrumbs')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item"><a href="#">Home</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.tenants.index') }}">Tenants</a></li>
        <li class="breadcrumb-item active">Create Tenant Default Data</li>
    </ol>
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card card-green-light card-outline">
                <div class="card-header">
                    <h3 class="card-title">Create Default Data For <strong class="text-info">{{ $tenant->name }}</strong>
                    </h3>
                </div>
                <form id="default-form" action="{{ route('admin.create.tenants.default.data', $tenant->id) }}"
                    method="post" autocomplete="off" data-parsley-validate="">
                    @csrf
                    <div class="card-body">
                        <x-errors />

                        <div class="row">
                            @foreach ($defaultType as $type)
                                <div class="col-md-2 col-sm-12">
                                    <label for="">{{ $type->label() }}</label>
                                    <x-admin::form.checkbox class="default-data-checkbox" name="{{ $type->value }}"
                                        label="Yes" checked-when="{{ in_array($type->value, $default_data) }}"
                                        disabled-when="{{ in_array($type->value, $default_data) }}" />
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="card-footer">
                        <x-button />
                        <x-admin::form.checkbox form-group-class="float-right" name="force_update" label="Force Update" />
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('js')
    <script src="{{ asset('vendor/admin/js/submit.on.validated.js') }}"></script>

    <script>
        $.ajaxSubmitOnValidated('default-form', {
            loader: 'btn-loader',
            errorType: 'toast',
        });

        $('#force_update').change(function() {
            if ($(this).is(':checked')) {
                $('.default-data-checkbox').removeAttr('disabled-when');
                $('.default-data-checkbox').removeAttr('disabled');
            } else {
                window.location.reload();
            }
        });
    </script>
@endpush
