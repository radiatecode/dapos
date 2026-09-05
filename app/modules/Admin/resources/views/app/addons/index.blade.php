@extends('admin::app.layouts.app')

@include('admin::app.partials._datatables')

@section('title', 'Add-ons')

@section('page_heading')
    Add-ons
@endsection

@section('breadcrumbs')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
        <li class="breadcrumb-item active">Add-ons</li>
    </ol>
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card admin-surface-card card-green-light card-outline">
                <div class="card-header admin-surface-card__header">
                    <div>
                        <h3 class="card-title">Add-ons</h3>
                        <p class="admin-card-hint mb-0">Optional billed extras that tenants can purchase.</p>
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
    <button type="button" id="open-addon-modal" class="d-none" data-bs-toggle="modal"
        data-bs-target="#addon-form-modal"></button>

    <div class="modal fade" id="addon-form-modal" tabindex="-1" aria-labelledby="addon-form-modal-title" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" id="addon-form" action="{{ route('admin.addons.store') }}" novalidate
                    data-parsley-validate="" autocomplete="off">
                    @csrf
                    <input type="hidden" name="_method" id="addon-form-method" value="POST">

                    <div class="modal-header">
                        <h5 class="modal-title" id="addon-form-modal-title">Create add-on</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <x-admin::form.input name="name" id="addon_name" label="Name" label-icon="fas fa-puzzle-piece"
                            required />
                        <x-admin::form.input name="code" id="addon_code" label="Code" label-icon="fas fa-barcode"
                            required />
                        <x-admin::form.textarea name="description" id="addon_description" label="Description"
                            label-icon="fas fa-align-left" rows="3" />
                        <x-admin::form.select2 name="billing_interval" id="addon_billing_interval"
                            label="Billing interval" label-icon="fas fa-calendar-alt"
                            selected="{{ \DA\Admin\Enums\BillingInterval::Monthly->value }}" required>
                            @foreach ($billingIntervals as $interval)
                                <option value="{{ $interval->value }}">{{ $interval->label() }}</option>
                            @endforeach
                        </x-admin::form.select2>
                        <x-admin::form.input name="price" id="addon_price" label="Price" label-icon="fas fa-tag"
                            type="number" step="0.01" min="0" default-value="9.00" required />
                        <x-admin::form.select2 name="currency_id" id="addon_currency_id" label="Currency"
                            label-icon="fas fa-coins" required>
                            <option value="">Select currency</option>
                            @foreach ($currencies as $currency)
                                <option value="{{ $currency->id }}">{{ $currency->code }} — {{ $currency->name }}
                                </option>
                            @endforeach
                        </x-admin::form.select2>
                        <input type="hidden" name="is_active" value="0">
                        <x-admin::form.checkbox name="is_active" id="addon_is_active" label="Active" value="1"
                            checked-when="true" />
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
            const $form = $('#addon-form');
            const storeUrl = @json(route('admin.addons.store'));

            const resetAddonForm = function() {
                $form.attr('action', storeUrl);
                $('#addon-form-method').val('POST');
                $('#addon-form-modal-title').text('Create add-on');
                $('#addon_name').val('');
                $('#addon_code').val('');
                $('#addon_description').val('');
                $('#addon_price').val('9.00');
                $('#addon_billing_interval').val(@json(\DA\Admin\Enums\BillingInterval::Monthly->value)).trigger('change');
                $('#addon_currency_id').val('').trigger('change');
                $('#addon_is_active').prop('checked', true);
                if ($form.parsley) {
                    $form.parsley().reset();
                }
                $.removeLaravelErrors();
            };

            $('#open-addon-modal').on('click', function() {
                resetAddonForm();
            });

            $(document).on('click', '.js-edit-addon', function() {
                const addon = JSON.parse($(this).attr('data-addon'));

                $form.attr('action', addon.update_url);
                $('#addon-form-method').val('PUT');
                $('#addon-form-modal-title').text('Edit add-on');
                $('#addon_name').val(addon.name);
                $('#addon_code').val(addon.code);
                $('#addon_description').val(addon.description ?? '');
                $('#addon_price').val(addon.price);
                $('#addon_billing_interval').val(addon.billing_interval).trigger('change');
                $('#addon_currency_id').val(String(addon.currency_id)).trigger('change');
                $('#addon_is_active').prop('checked', addon.is_active);

                bootstrap.Modal.getOrCreateInstance(document.getElementById('addon-form-modal')).show();
            });

            $.ajaxSubmitOnValidated('addon-form', {
                loader: 'btn-loader',
                errorType: 'inline',
                redirectTo: function() {
                    bootstrap.Modal.getOrCreateInstance(document.getElementById('addon-form-modal')).hide();
                    if (window.LaravelDataTables && LaravelDataTables['addons-table']) {
                        LaravelDataTables['addons-table'].ajax.reload(null, false);
                    } else {
                        window.location.reload();
                    }
                },
            });
        });
    </script>
@endpush
