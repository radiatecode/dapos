@extends('admin::app.layouts.app')

@section('title', $plan->name)

@section('page_heading')
    Plan details
@endsection

@section('breadcrumbs')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.plans.index') }}">Plans</a></li>
        <li class="breadcrumb-item active">{{ $plan->name }}</li>
    </ol>
@endsection

@section('content')
    <div class="tenant-page">
        <section class="tenant-hero">
            <div class="tenant-hero__body">
                <div class="tenant-avatar" aria-hidden="true">
                    <span>{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($plan->name, 0, 1)) }}</span>
                </div>

                <div class="tenant-hero__copy">
                    <p class="tenant-kicker">Plan</p>
                    <h2 class="tenant-hero__name">{{ $plan->name }}</h2>
                    <p class="tenant-hero__slug">{{ $plan->code }}</p>

                    <div class="tenant-chips">
                        <span class="tenant-chip">
                            <i class="fas fa-tag"></i>
                            {{ $plan->currency?->symbol }}{{ $plan->price }} / {{ $plan->billing_interval->label() }}
                        </span>
                        <span class="tenant-chip">
                            <i class="fas fa-hourglass-half"></i>
                            {{ $plan->trial_days }} trial days
                        </span>
                    </div>
                </div>

                <span class="tenant-status tenant-status--{{ $plan->isActive() ? 'success' : 'danger' }}">
                    {{ $plan->isActive() ? 'Active' : 'Inactive' }}
                </span>
            </div>
        </section>

        <div class="row">
            <div class="col-lg-8">
                @include('admin::app.tenants._info_section', [
                    'title' => 'Plan Info',
                    'icon' => 'fas fa-info-circle',
                    'rows' => [
                        'Name' => $plan->name,
                        'Code' => $plan->code,
                        'Description' => $plan->description,
                        'Billing interval' => $plan->billing_interval->label(),
                        'Price' => ($plan->currency?->code ?? '').' '.$plan->price,
                        'Currency' => $plan->currency?->name,
                        'Trial days' => $plan->trial_days,
                        'Status' => $plan->isActive() ? 'Active' : 'Inactive',
                    ],
                ])

                <div class="card admin-surface-card tenant-section mb-3">
                    <div class="card-header admin-surface-card__header tenant-section__header">
                        <span class="tenant-section__icon">
                            <i class="fas fa-star"></i>
                        </span>
                        <h3 class="card-title mb-0">Features</h3>
                    </div>
                    <div class="card-body">
                        @if ($plan->planFeatures->isEmpty())
                            <p class="admin-card-hint mb-0">No features assigned to this plan.</p>
                        @else
                            <dl class="tenant-info-grid tenant-info-list mb-0">
                                @foreach ($plan->planFeatures as $assignment)
                                    <div class="tenant-info-item">
                                        <dt>{{ $assignment->feature?->name ?? 'Feature' }}</dt>
                                        <dd>{{ $assignment->displayValue() }}</dd>
                                    </div>
                                @endforeach
                            </dl>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card admin-surface-card tenant-actions">
                    <div class="card-header admin-surface-card__header">
                        <h3 class="card-title">Actions</h3>
                    </div>
                    <div class="card-body">
                        <div class="tenant-actions__status">
                            <span>Current status</span>
                            <span class="tenant-status tenant-status--{{ $plan->isActive() ? 'success' : 'danger' }}">
                                {{ $plan->isActive() ? 'Active' : 'Inactive' }}
                            </span>
                        </div>

                        <a href="{{ route('admin.plans.edit', $plan) }}" class="btn admin-btn btn-primary tenant-action-btn">
                            <i class="fas fa-edit"></i>
                            <span>Edit</span>
                        </a>

                        @if ($plan->isActive())
                            <form method="POST" action="{{ route('admin.plans.deactivate', $plan) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn admin-btn btn-warning tenant-action-btn">
                                    <i class="fas fa-ban"></i>
                                    <span>Deactivate</span>
                                </button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('admin.plans.activate', $plan) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn admin-btn btn-success tenant-action-btn">
                                    <i class="fas fa-check-circle"></i>
                                    <span>Activate</span>
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
