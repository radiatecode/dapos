@extends('admin::app.layouts.app')

@section('title', $tenant->name)

@section('page_heading')
    Tenant details
@endsection

@section('breadcrumbs')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.tenants.index') }}">Tenants</a></li>
        <li class="breadcrumb-item active">{{ $tenant->name }}</li>
    </ol>
@endsection

@section('content')
    <div class="tenant-page">
        <section class="tenant-hero">
            <div class="tenant-hero__body">
                <div class="tenant-avatar" aria-hidden="true">
                    @if ($tenant->logo)
                        <img src="{{ asset('storage/'.$tenant->logo) }}" alt="{{ $tenant->name }} logo">
                    @else
                        <span>{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($tenant->name, 0, 1)) }}</span>
                    @endif
                </div>

                <div class="tenant-hero__copy">
                    <p class="tenant-kicker">Tenant</p>
                    <h2 class="tenant-hero__name">{{ $tenant->name }}</h2>
                    <p class="tenant-hero__slug">{{ $tenant->slug }}</p>

                    <div class="tenant-chips">
                        @if (filled($tenant->timezone))
                            <span class="tenant-chip"><i class="fas fa-globe"></i> {{ $tenant->timezone }}</span>
                        @endif
                        @if (filled($tenant->currency))
                            <span class="tenant-chip"><i class="fas fa-coins"></i> {{ $tenant->currency }}</span>
                        @endif
                        @if (filled($tenant->city) || filled($tenant->country))
                            <span class="tenant-chip">
                                <i class="fas fa-map-marker-alt"></i>
                                {{ collect([$tenant->city, $tenant->country])->filter()->implode(', ') }}
                            </span>
                        @endif
                    </div>
                </div>

                <span class="tenant-status tenant-status--{{ $tenant->status->color() }}">
                    {{ $tenant->status->label() }}
                </span>
            </div>
        </section>

        <div class="row">
            <div class="col-lg-8">
                @include('admin::app.tenants._info_section', [
                    'title' => 'Basic Info',
                    'icon' => 'fas fa-info-circle',
                    'rows' => [
                        'Name' => $tenant->name,
                        'Slug' => $tenant->slug,
                        'Status' => $tenant->status->label(),
                        'Timezone' => $tenant->timezone,
                        'Currency' => $tenant->currency,
                        'Address line 1' => $tenant->address_line_1,
                        'Address line 2' => $tenant->address_line_2,
                        'City' => $tenant->city,
                        'State' => $tenant->state,
                        'Postal code' => $tenant->postal_code,
                        'Country' => $tenant->country,
                    ],
                ])

                @include('admin::app.tenants._info_section', [
                    'title' => 'Contact',
                    'icon' => 'fas fa-user',
                    'rows' => [
                        'Contact person name' => $tenant->contact_person_name,
                        'Contact person email' => $tenant->contact_person_email,
                        'Contact person phone' => $tenant->contact_person_phone,
                        'Website' => $tenant->website,
                    ],
                ])

                @include('admin::app.tenants._info_section', [
                    'title' => 'Billing Info',
                    'icon' => 'fas fa-file-invoice-dollar',
                    'rows' => [
                        'Billing name' => $tenant->billing_name,
                        'Billing email' => $tenant->billing_email,
                        'Billing phone' => $tenant->billing_phone,
                        'Billing address line 1' => $tenant->billing_address_line_1,
                        'Billing address line 2' => $tenant->billing_address_line_2,
                        'Billing city' => $tenant->billing_city,
                        'Billing state' => $tenant->billing_state,
                        'Billing postal code' => $tenant->billing_postal_code,
                        'Billing country' => $tenant->billing_country,
                    ],
                ])
            </div>

            <div class="col-lg-4">
                <div class="card admin-surface-card tenant-actions">
                    <div class="card-header admin-surface-card__header">
                        <h3 class="card-title">Actions</h3>
                    </div>
                    <div class="card-body">
                        <div class="tenant-actions__status">
                            <span>Current status</span>
                            <span class="tenant-status tenant-status--{{ $tenant->status->color() }}">
                                {{ $tenant->status->label() }}
                            </span>
                        </div>

                        <a href="{{ route('admin.tenants.edit', $tenant) }}" class="btn admin-btn btn-primary tenant-action-btn">
                            <i class="fas fa-edit"></i>
                            <span>Edit</span>
                        </a>

                        @if ($tenant->isSuspended())
                            <form method="POST" action="{{ route('admin.tenants.activate', $tenant) }}">
                                @csrf
                                <button type="submit" class="btn admin-btn btn-success tenant-action-btn">
                                    <i class="fas fa-check-circle"></i>
                                    <span>Activate</span>
                                </button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('admin.tenants.suspend', $tenant) }}">
                                @csrf
                                <button type="submit" class="btn admin-btn btn-warning tenant-action-btn">
                                    <i class="fas fa-ban"></i>
                                    <span>Suspend</span>
                                </button>
                            </form>
                        @endif

                        <form method="POST" action="{{ route('admin.tenants.destroy', $tenant) }}"
                            class="tenant-actions__danger"
                            onsubmit="return confirm('Delete this tenant?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn admin-btn btn-danger tenant-action-btn">
                                <i class="fas fa-trash"></i>
                                <span>Delete</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
