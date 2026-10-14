<?php

namespace DA\Admin\Actions\Tenant;

use App\Models\User;
use DA\Admin\Enums\TenantStatus;
use DA\Admin\Models\Tenant;

class ActivateTenantAccess
{
    public function handle(Tenant $tenant): Tenant
    {
        if ($tenant->isSuspended()) {
            return $tenant;
        }

        if ($tenant->status === TenantStatus::Inactive) {
            $tenant->status = TenantStatus::Active;
            $tenant->save();
        }

        User::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_super_admin', true)
            ->where('is_active', false)
            ->update(['is_active' => true]);

        return $tenant->refresh();
    }
}
