@extends('admin::app.layouts.app')

@section('title', 'Generate invoice')

@section('page_heading')
    Generate invoice
@endsection

@section('breadcrumbs')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.invoices.index') }}">Invoices</a></li>
        <li class="breadcrumb-item active">Generate</li>
    </ol>
@endsection

@section('content')
    <div class="admin-form-page">
        <form method="POST" action="{{ route('admin.invoices.store') }}" novalidate data-parsley-validate=""
            autocomplete="off">
            @csrf

            <div class="row">
                <div class="col-12">
                    <x-admin::boot-card title="Generate invoice">
                        <x-slot:header>
                            <p class="admin-card-hint mb-0">Create an invoice from the current subscription period. A coupon is optional.</p>
                        </x-slot:header>

                        <div class="row">
                            <div class="col-md-8">
                                <x-admin::form.select2 name="subscription_id" label="Subscription"
                                    label-icon="fas fa-sync-alt" selected="{{ old('subscription_id') }}" required>
                                    <option value="">Select subscription</option>
                                    @foreach ($subscriptions as $subscription)
                                        <option value="{{ $subscription->id }}">
                                            {{ $subscription->tenant?->name }} — {{ $subscription->plan?->name }}
                                            (#{{ $subscription->id }})
                                        </option>
                                    @endforeach
                                </x-admin::form.select2>
                            </div>
                            <div class="col-md-4">
                                <x-admin::form.input name="coupon_code" label="Coupon code" label-icon="fas fa-ticket-alt"
                                    default-value="{{ old('coupon_code') }}" />
                            </div>
                        </div>

                        <x-slot:footer>
                            <a href="{{ route('admin.invoices.index') }}" class="btn admin-btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Back
                            </a>
                            <x-admin::button type="submit" class="btn-primary" text="Generate" icon="fas fa-file-invoice" />
                        </x-slot:footer>
                    </x-admin::boot-card>
                </div>
            </div>
        </form>
    </div>
@endsection
