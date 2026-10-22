@php
    /** @var \DA\Admin\Models\Plan|null $plan */
    $plan ??= null;
    $assigned = $plan?->planFeatures?->keyBy('feature') ?? collect();
@endphp

<div class="row">
    <div class="col-md-6">
        <x-admin::form.input name="name" label="Name" label-icon="fas fa-layer-group"
            default-value="{{ $plan?->name }}" required />
    </div>
    <div class="col-md-6">
        <x-admin::form.input name="code" label="Code" label-icon="fas fa-barcode" default-value="{{ $plan?->code }}"
            required />
    </div>
    <div class="col-12">
        <x-admin::form.textarea name="description" label="Description" label-icon="fas fa-align-left" rows="3"
            default-value="{{ $plan?->description }}" />
    </div>
    <div class="col-md-6">
        <x-admin::form.select2 name="billing_interval" label="Billing interval" label-icon="fas fa-calendar-alt"
            selected="{{ old('billing_interval', $plan?->billing_interval?->value ?? \DA\Admin\Enums\BillingInterval::Monthly->value) }}"
            required>
            @foreach ($billingIntervals as $interval)
                <option value="{{ $interval->value }}">{{ $interval->label() }}</option>
            @endforeach
        </x-admin::form.select2>
    </div>
    <div class="col-md-6">
        <x-admin::form.input name="price" label="Price" label-icon="fas fa-tag" type="number" step="0.01"
            min="0" default-value="{{ $plan?->price ?? '19.00' }}" required />
    </div>
    <div class="col-md-6">
        <x-admin::form.select2 name="currency_id" label="Currency" label-icon="fas fa-coins"
            selected="{{ old('currency_id', $plan?->currency_id) }}" required>
            <option value="">Select currency</option>
            @foreach ($currencies as $currency)
                <option value="{{ $currency->id }}">{{ $currency->code }} — {{ $currency->name }}</option>
            @endforeach
        </x-admin::form.select2>
    </div>
    <div class="col-md-6">
        <x-admin::form.input name="trial_days" label="Trial days" label-icon="fas fa-hourglass-half" type="number"
            min="0" max="365" default-value="{{ $plan?->trial_days ?? 0 }}" />
    </div>
    <div class="col-md-6">
        <input type="hidden" name="is_active" value="0">
        <x-admin::form.checkbox name="is_active" label="Active" value="1"
            checked-when="{{ old('is_active', $plan?->is_active ?? true) }}" />
    </div>
</div>

@include('admin::app.plans._feature_editor')
