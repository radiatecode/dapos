<?php

namespace DA\Admin\Services\Tenancy;

use DA\Admin\Exceptions\TenantContextMissingException;
use DA\Admin\Models\Tenant;
use Illuminate\Support\Facades\Context;

class TenantContext
{
    private ?Tenant $tenant = null;

    public function set(Tenant $tenant): void
    {
        $this->tenant = $tenant;

        Context::add('tenant_id', $tenant->id);
    }

    public function get(): Tenant
    {
        if ($this->tenant instanceof Tenant) {
            return $this->tenant;
        }

        $tenantId = Context::get('tenant_id');

        if ($tenantId === null) {
            throw new TenantContextMissingException;
        }

        $tenant = Tenant::query()->find($tenantId);

        if (! $tenant instanceof Tenant) {
            throw new TenantContextMissingException;
        }

        $this->tenant = $tenant;

        return $this->tenant;
    }

    public function id(): ?int
    {
        if ($this->tenant instanceof Tenant) {
            return $this->tenant->id;
        }

        $tenantId = Context::get('tenant_id');

        return $tenantId !== null ? (int) $tenantId : null;
    }

    public function has(): bool
    {
        return $this->id() !== null;
    }

    public function forget(): void
    {
        $this->tenant = null;

        Context::forget('tenant_id');
    }
}
