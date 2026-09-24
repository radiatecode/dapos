@php
    /** @var \DA\Admin\Models\Plan|null $plan */
    $plan ??= null;
    $assigned = $plan?->planFeatures?->keyBy('feature_id') ?? collect();
@endphp

<div class="row">
    <div class="col-md-6">
        <x-admin::form.input name="name" label="Name" label-icon="fas fa-layer-group"
            default-value="{{ $plan?->name }}" required />
    </div>
    <div class="col-md-6">
        <x-admin::form.input name="code" label="Code" label-icon="fas fa-barcode"
            default-value="{{ $plan?->code }}" required />
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
        <x-admin::form.input name="price" label="Price" label-icon="fas fa-tag" type="number" step="0.01" min="0"
            default-value="{{ $plan?->price ?? '19.00' }}" required />
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

<div class="plan-feature-editor mt-4">
    <div class="plan-feature-editor__head">
        <h4>Features</h4>
        <p class="admin-card-hint mb-0">Assign boolean flags, numeric limits, or unlimited access.</p>
    </div>

    @if ($features->isEmpty())
        <p class="admin-card-hint mb-0">No features yet. Create features first, then assign them here.</p>
    @else
        <div class="table-responsive">
            <table class="table admin-table plan-feature-table">
                <thead>
                    <tr>
                        <th>Assign</th>
                        <th>Feature</th>
                        <th>Type</th>
                        <th>Value</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($features as $feature)
                        @php
                            $pivot = $assigned->get($feature->id);
                            $isAssigned = old("features.{$feature->id}.assigned", $pivot !== null);
                            $isUnlimited = old("features.{$feature->id}.is_unlimited", $pivot?->is_unlimited ?? false);
                            $value = old("features.{$feature->id}.value", $pivot?->value);
                        @endphp
                        <tr class="plan-feature-row" data-type="{{ $feature->type->value }}">
                            <td>
                                <input type="hidden" name="features[{{ $feature->id }}][feature_id]"
                                    value="{{ $feature->id }}">
                                <input type="hidden" name="features[{{ $feature->id }}][assigned]" value="0">
                                <div class="custom-control custom-checkbox admin-check">
                                    <input class="custom-control-input js-feature-assigned" type="checkbox"
                                        id="feature_assigned_{{ $feature->id }}"
                                        name="features[{{ $feature->id }}][assigned]" value="1"
                                        {{ $isAssigned ? 'checked' : '' }}>
                                    <label class="custom-control-label"
                                        for="feature_assigned_{{ $feature->id }}">Include</label>
                                </div>
                            </td>
                            <td>
                                <strong>{{ $feature->name }}</strong>
                                <div class="text-muted small">{{ $feature->code }}</div>
                            </td>
                            <td>{{ $feature->type->label() }}</td>
                            <td>
                                @if ($feature->isBoolean())
                                    <select name="features[{{ $feature->id }}][value]"
                                        class="form-control admin-input js-feature-value">
                                        <option value="0" @selected($value !== '1')>No</option>
                                        <option value="1" @selected($value === '1')>Yes</option>
                                    </select>
                                @else
                                    <div class="plan-limit-controls">
                                        <input type="number" min="0"
                                            name="features[{{ $feature->id }}][value]"
                                            class="form-control admin-input js-feature-value"
                                            value="{{ $isUnlimited ? '' : $value }}"
                                            {{ $isUnlimited ? 'disabled' : '' }}>
                                        <input type="hidden" name="features[{{ $feature->id }}][is_unlimited]"
                                            value="0">
                                        <div class="custom-control custom-checkbox admin-check">
                                            <input class="custom-control-input js-feature-unlimited" type="checkbox"
                                                id="feature_unlimited_{{ $feature->id }}"
                                                name="features[{{ $feature->id }}][is_unlimited]" value="1"
                                                {{ $isUnlimited ? 'checked' : '' }}>
                                            <label class="custom-control-label"
                                                for="feature_unlimited_{{ $feature->id }}">Unlimited</label>
                                        </div>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
