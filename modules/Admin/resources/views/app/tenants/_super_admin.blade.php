@php
    /** @var \DA\Admin\Models\Tenant $tenant */
    $superAdmin = $tenant->superAdmin;
@endphp

<div class="card admin-surface-card tenant-section mb-3">
    <div class="card-header admin-surface-card__header tenant-section__header">
        <span class="tenant-section__icon">
            <i class="fas fa-user-shield"></i>
        </span>
        <h3 class="card-title mb-0">Super admin</h3>
    </div>
    <div class="card-body">
        @if ($superAdmin)
            <dl class="tenant-info-grid tenant-info-list mb-0">
                <div class="tenant-info-item">
                    <dt>Name</dt>
                    <dd>{{ $superAdmin->name }}</dd>
                </div>
                <div class="tenant-info-item">
                    <dt>Email</dt>
                    <dd>{{ $superAdmin->email }}</dd>
                </div>
                <div class="tenant-info-item">
                    <dt>Account</dt>
                    <dd>
                        <span class="tenant-status tenant-status--{{ $superAdmin->isActive() ? 'success' : 'muted' }}">
                            {{ $superAdmin->isActive() ? 'Active' : 'Inactive' }}
                        </span>
                    </dd>
                </div>
            </dl>
        @else
            <p class="admin-card-hint">
                This account stays inactive until the tenant subscribes to a plan.
            </p>

            <x-admin::errors />

            <form method="POST" action="{{ route('admin.tenants.super-admin.store', $tenant) }}" autocomplete="off">
                @csrf
                <div class="row">
                    <div class="col-md-6">
                        <x-admin::form.input name="name" label="Name" label-icon="fas fa-user" required
                            autocomplete="off" />
                    </div>
                    <div class="col-md-6">
                        <x-admin::form.input name="email" type="email" label="Email" label-icon="fas fa-envelope"
                            required autocomplete="off" />
                    </div>
                    <div class="col-md-6">
                        <x-admin::form.input name="password" type="password" label="Password" label-icon="fas fa-lock"
                            required autocomplete="new-password" help-block="At least 8 characters." />
                    </div>
                    <div class="col-md-6">
                        <x-admin::form.input name="password_confirmation" type="password" label="Confirm password"
                            label-icon="fas fa-lock" required autocomplete="new-password" />
                    </div>
                </div>
                <x-admin::button type="submit" class="btn-primary" text="Create super admin" icon="fas fa-user-plus" />
            </form>
        @endif
    </div>
</div>
