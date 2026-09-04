@extends('admin::layouts.app')

@section('title', 'Dashboard')

@section('content')
    <x-admin::page-header
        kicker="Overview"
        title="Dashboard"
        subtitle="A live pulse of the provider control plane."
    />

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <x-admin::stat-card label="Tenants" value="—" hint="Provisioning arrives in a later phase" icon="bi-building" />
        </div>
        <div class="col-md-4">
            <x-admin::stat-card label="Subscriptions" value="—" hint="Billing is not enabled yet" icon="bi-arrow-repeat" />
        </div>
        <div class="col-md-4">
            <x-admin::stat-card label="Your permissions" :value="auth('admin')->user()?->adminPermissionKeys()->count() ?? 0" hint="Assigned through admin roles" icon="bi-shield-check" />
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-8">
            <x-admin::card title="Getting started">
                <p class="text-secondary mb-3">
                    Authentication, roles and the admin shell are ready. Tenant, plan and billing modules will land on this same template.
                </p>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('admin.profile') }}" class="btn btn-admin">View profile</a>
                    @can('permissions.view')
                        <a href="{{ route('admin.permissions') }}" class="btn btn-outline-dark rounded-3">Review permissions</a>
                    @endcan
                </div>
            </x-admin::card>
        </div>
        <div class="col-lg-4">
            <x-admin::card title="Session">
                <div class="d-flex align-items-center gap-3">
                    <span class="admin-avatar">{{ strtoupper(substr(auth('admin')->user()?->name ?? 'A', 0, 1)) }}</span>
                    <div>
                        <div class="fw-bold">{{ auth('admin')->user()?->name }}</div>
                        <div class="text-secondary small">{{ auth('admin')->user()?->email }}</div>
                    </div>
                </div>
            </x-admin::card>
        </div>
    </div>
@endsection
