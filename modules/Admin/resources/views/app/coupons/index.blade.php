@extends('admin::app.layouts.app')

@include('admin::app.partials._datatables')

@section('title', 'Coupons')

@section('page_heading')
    Coupons
@endsection

@section('breadcrumbs')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
        <li class="breadcrumb-item active">Coupons</li>
    </ol>
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card admin-surface-card card-green-light card-outline">
                <div class="card-header admin-surface-card__header">
                    <div>
                        <h3 class="card-title">Coupons</h3>
                        <p class="admin-card-hint mb-0">Percentage and fixed discounts that can be applied when generating invoices.</p>
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
    <button type="button" id="open-coupon-modal" class="d-none" data-bs-toggle="modal"
        data-bs-target="#coupon-form-modal"></button>

    <div class="modal fade" id="coupon-form-modal" tabindex="-1" aria-labelledby="coupon-form-modal-title" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" id="coupon-form" action="{{ route('admin.coupons.store') }}" novalidate
                    data-parsley-validate="" autocomplete="off">
                    @csrf
                    <input type="hidden" name="_method" id="coupon-form-method" value="POST">

                    <div class="modal-header">
                        <h5 class="modal-title" id="coupon-form-modal-title">Create coupon</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <x-admin::form.input name="name" id="coupon_name" label="Name" label-icon="fas fa-ticket-alt"
                            required />
                        <x-admin::form.input name="code" id="coupon_code" label="Code" label-icon="fas fa-barcode"
                            required />
                        <x-admin::form.textarea name="description" id="coupon_description" label="Description"
                            label-icon="fas fa-align-left" rows="2" />
                        <x-admin::form.select2 name="discount_type" id="coupon_discount_type" label="Discount type"
                            label-icon="fas fa-percent" required>
                            @foreach ($discountTypes as $type)
                                <option value="{{ $type->value }}">{{ $type->label() }}</option>
                            @endforeach
                        </x-admin::form.select2>
                        <x-admin::form.input name="discount_value" id="coupon_discount_value" label="Discount value"
                            label-icon="fas fa-tag" type="number" step="0.01" min="0" default-value="10.00"
                            required />
                        <x-admin::form.select2 name="currency_id" id="coupon_currency_id" label="Currency"
                            label-icon="fas fa-coins">
                            <option value="">Select currency</option>
                            @foreach ($currencies as $currency)
                                <option value="{{ $currency->id }}">{{ $currency->code }} — {{ $currency->name }}
                                </option>
                            @endforeach
                        </x-admin::form.select2>
                        <x-admin::form.input name="max_redemptions" id="coupon_max_redemptions" label="Max redemptions"
                            label-icon="fas fa-hashtag" type="number" min="1" />
                        <x-admin::form.input name="max_redemptions_per_tenant" id="coupon_max_redemptions_per_tenant"
                            label="Max per tenant" label-icon="fas fa-user" type="number" min="1" default-value="1" />
                        <x-admin::form.input name="minimum_amount" id="coupon_minimum_amount" label="Minimum subtotal"
                            label-icon="fas fa-coins" type="number" step="0.01" min="0" />
                        <x-admin::form.input name="starts_at" id="coupon_starts_at" label="Starts at"
                            label-icon="fas fa-calendar" type="datetime-local" />
                        <x-admin::form.input name="ends_at" id="coupon_ends_at" label="Ends at"
                            label-icon="fas fa-calendar" type="datetime-local" />
                        <input type="hidden" name="is_active" value="0">
                        <x-admin::form.checkbox name="is_active" id="coupon_is_active" label="Active" value="1"
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
            const $form = $('#coupon-form');
            const storeUrl = @json(route('admin.coupons.store'));

            const resetCouponForm = function() {
                $form.attr('action', storeUrl);
                $('#coupon-form-method').val('POST');
                $('#coupon-form-modal-title').text('Create coupon');
                $('#coupon_name').val('');
                $('#coupon_code').val('');
                $('#coupon_description').val('');
                $('#coupon_discount_value').val('10.00');
                $('#coupon_discount_type').val(@json(\DA\Admin\Enums\CouponDiscountType::Percentage->value)).trigger('change');
                $('#coupon_currency_id').val('').trigger('change');
                $('#coupon_max_redemptions').val('');
                $('#coupon_max_redemptions_per_tenant').val('1');
                $('#coupon_minimum_amount').val('');
                $('#coupon_starts_at').val('');
                $('#coupon_ends_at').val('');
                $('#coupon_is_active').prop('checked', true);
                if ($form.parsley) {
                    $form.parsley().reset();
                }
                $.removeLaravelErrors();
            };

            $('#open-coupon-modal').on('click', function() {
                resetCouponForm();
            });

            $(document).on('click', '.js-edit-coupon', function() {
                const coupon = JSON.parse($(this).attr('data-coupon'));

                $form.attr('action', coupon.update_url);
                $('#coupon-form-method').val('PUT');
                $('#coupon-form-modal-title').text('Edit coupon');
                $('#coupon_name').val(coupon.name);
                $('#coupon_code').val(coupon.code);
                $('#coupon_description').val(coupon.description ?? '');
                $('#coupon_discount_value').val(coupon.discount_value);
                $('#coupon_discount_type').val(coupon.discount_type).trigger('change');
                $('#coupon_currency_id').val(coupon.currency_id ? String(coupon.currency_id) : '').trigger('change');
                $('#coupon_max_redemptions').val(coupon.max_redemptions ?? '');
                $('#coupon_max_redemptions_per_tenant').val(coupon.max_redemptions_per_tenant ?? '');
                $('#coupon_minimum_amount').val(coupon.minimum_amount ?? '');
                $('#coupon_starts_at').val(coupon.starts_at ? coupon.starts_at.slice(0, 16) : '');
                $('#coupon_ends_at').val(coupon.ends_at ? coupon.ends_at.slice(0, 16) : '');
                $('#coupon_is_active').prop('checked', coupon.is_active);

                bootstrap.Modal.getOrCreateInstance(document.getElementById('coupon-form-modal')).show();
            });

            $.ajaxSubmitOnValidated('coupon-form', {
                loader: 'btn-loader',
                errorType: 'inline',
                redirectTo: function() {
                    bootstrap.Modal.getOrCreateInstance(document.getElementById('coupon-form-modal')).hide();
                    if (window.LaravelDataTables && LaravelDataTables['coupons-table']) {
                        LaravelDataTables['coupons-table'].ajax.reload(null, false);
                    } else {
                        window.location.reload();
                    }
                },
            });
        });
    </script>
@endpush
