@extends('admin::app.layouts.app')

@section('title', 'Create subscription')

@section('page_heading')
    Create subscription
@endsection

@section('breadcrumbs')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.subscriptions.index') }}">Subscriptions</a></li>
        <li class="breadcrumb-item active">Create</li>
    </ol>
@endsection

@section('content')
    <div class="admin-form-page">
        <form method="POST" id="create-subscription-form" action="{{ route('admin.subscriptions.store') }}" novalidate
            data-parsley-validate="" data-parsley-excluded="input[type=hidden], [disabled]" autocomplete="off">
            @csrf

            <div class="row">
                <div class="col-12">
                    <x-admin::boot-card title="Create subscription">
                        <x-slot:header>
                            <p class="admin-card-hint mb-0">Assign a plan to a tenant. Start a trial when the plan allows it.</p>
                        </x-slot:header>

                        <div class="row">
                            <div class="col-md-6">
                                <x-admin::form.select2 name="tenant_id" label="Tenant" label-icon="fas fa-building"
                                    selected="{{ old('tenant_id') }}" required>
                                    <option value="">Select tenant</option>
                                    @foreach ($tenants as $tenant)
                                        <option value="{{ $tenant->id }}">{{ $tenant->name }}</option>
                                    @endforeach
                                </x-admin::form.select2>
                            </div>
                            <div class="col-md-6">
                                <x-admin::form.select2 name="plan_id" label="Plan" label-icon="fas fa-layer-group"
                                    selected="{{ old('plan_id') }}" required>
                                    <option value="">Select plan</option>
                                    @foreach ($plans as $plan)
                                        <option value="{{ $plan->id }}">
                                            {{ $plan->name }} — {{ $plan->currency?->code }} {{ $plan->price }} /
                                            {{ $plan->billing_interval->label() }}
                                        </option>
                                    @endforeach
                                </x-admin::form.select2>
                            </div>
                            <div class="col-md-6">
                                <x-admin::form.select2 name="addon_ids[]" id="addon_ids" label="Add-ons"
                                    label-icon="fas fa-puzzle-piece" multiple>
                                    @foreach ($addons as $addon)
                                        <option value="{{ $addon->id }}" @selected(in_array($addon->id, old('addon_ids', []), false))>
                                            {{ $addon->name }} — {{ $addon->currency?->code }} {{ $addon->price }}
                                        </option>
                                    @endforeach
                                </x-admin::form.select2>
                            </div>
                            <div class="col-md-6">
                                <x-admin::form.input name="grace_days" type="number" label="Grace days"
                                    label-icon="fas fa-hourglass-half"
                                    default-value="{{ old('grace_days', \DA\Admin\Models\Subscription::DEFAULT_GRACE_DAYS) }}"
                                    min="0" max="365" required
                                    help-block="Access continues this many days after the billing period ends." />
                            </div>
                            <div class="col-md-6">
                                <x-admin::form.checkbox name="start_trial" label="Start trial when the plan has trial days"
                                    value="1" checked-when="{{ old('start_trial', true) }}" />
                            </div>
                        </div>

                        <x-slot:footer>
                            <a href="{{ route('admin.subscriptions.index') }}" class="btn admin-btn btn-secondary">
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
    <script>
        $(function() {
            $.ajaxSubmitOnValidated('create-subscription-form', {
                loader: 'btn-loader',
                errorType: 'inline',
                redirectTo: function(response) {
                    window.location.href = @json(route('admin.subscriptions.index'));
                },
            });
        });
    </script>
@endpush
