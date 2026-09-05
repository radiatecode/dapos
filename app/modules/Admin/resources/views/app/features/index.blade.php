@extends('admin::app.layouts.app')

@include('admin::app.partials._datatables')

@section('title', 'Features')

@section('page_heading')
    Features
@endsection

@section('breadcrumbs')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
        <li class="breadcrumb-item active">Features</li>
    </ol>
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card admin-surface-card card-green-light card-outline">
                <div class="card-header admin-surface-card__header">
                    <div>
                        <h3 class="card-title">Features</h3>
                        <p class="admin-card-hint mb-0">Boolean flags and numeric limits that plans can assign.</p>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive admin-table-wrap">
                        {{ $dataTable->table(['class' => 'table table-hover admin-table'], true) }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('modals')
    <button type="button" id="open-feature-modal" class="d-none" data-bs-toggle="modal"
        data-bs-target="#feature-form-modal"></button>

    <div class="modal fade" id="feature-form-modal" tabindex="-1" aria-labelledby="feature-form-modal-title"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" id="feature-form" action="{{ route('admin.features.store') }}" novalidate
                    data-parsley-validate="" autocomplete="off">
                    @csrf
                    <input type="hidden" name="_method" id="feature-form-method" value="POST">

                    <div class="modal-header">
                        <h5 class="modal-title" id="feature-form-modal-title">Create feature</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <x-admin::form.input name="name" id="feature_name" label="Name" label-icon="fas fa-star"
                            required />
                        <x-admin::form.input name="code" id="feature_code" label="Code" label-icon="fas fa-barcode"
                            required />
                        <x-admin::form.select2 name="type" id="feature_type" label="Type" label-icon="fas fa-sliders-h"
                            selected="{{ \DA\Admin\Enums\FeatureType::Limit->value }}" required>
                            @foreach ($featureTypes as $type)
                                <option value="{{ $type->value }}">{{ $type->label() }}</option>
                            @endforeach
                        </x-admin::form.select2>
                        <x-admin::form.textarea name="description" id="feature_description" label="Description"
                            label-icon="fas fa-align-left" rows="3" />
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn admin-btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <x-admin::button type="submit" class="btn-primary" text="Save" icon="fas fa-save" />
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('js')
    {{ $dataTable->scripts() }}
    <script src="{{ asset('vendor/admin/js/submit.on.validated.js') }}?v={{ filemtime(public_path('vendor/admin/js/submit.on.validated.js')) }}"></script>
    <script>
        $(function() {
            const $form = $('#feature-form');
            const storeUrl = @json(route('admin.features.store'));

            const resetFeatureForm = function() {
                $form.attr('action', storeUrl);
                $('#feature-form-method').val('POST');
                $('#feature-form-modal-title').text('Create feature');
                $('#feature_name').val('');
                $('#feature_code').val('');
                $('#feature_description').val('');
                $('#feature_type').val(@json(\DA\Admin\Enums\FeatureType::Limit->value)).trigger('change');
                if ($form.parsley) {
                    $form.parsley().reset();
                }
                $.removeLaravelErrors();
            };

            $('#feature-form-modal').on('show.bs.modal', function(event) {
                if (!$(event.relatedTarget).hasClass('js-edit-feature')) {
                    resetFeatureForm();
                }
            });

            $('#open-feature-modal').on('click', function() {
                resetFeatureForm();
            });

            $(document).on('click', '.js-edit-feature', function() {
                const feature = JSON.parse($(this).attr('data-feature'));

                $form.attr('action', feature.update_url);
                $('#feature-form-method').val('PUT');
                $('#feature-form-modal-title').text('Edit feature');
                $('#feature_name').val(feature.name);
                $('#feature_code').val(feature.code);
                $('#feature_description').val(feature.description ?? '');
                $('#feature_type').val(feature.type).trigger('change');

                bootstrap.Modal.getOrCreateInstance(document.getElementById('feature-form-modal')).show();
            });

            $.ajaxSubmitOnValidated('feature-form', {
                loader: 'btn-loader',
                errorType: 'inline',
                redirectTo: function() {
                    bootstrap.Modal.getOrCreateInstance(document.getElementById('feature-form-modal')).hide();
                    if (window.LaravelDataTables && LaravelDataTables['features-table']) {
                        LaravelDataTables['features-table'].ajax.reload(null, false);
                    } else {
                        window.location.reload();
                    }
                },
            });
        });
    </script>
@endpush
