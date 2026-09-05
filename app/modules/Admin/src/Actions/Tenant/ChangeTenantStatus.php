<?php

namespace DA\Admin\Actions\Tenant;

use DA\Admin\Enums\TenantStatus;
use DA\Admin\Models\Tenant;

class ChangeTenantStatus
{
    public function handle(Tenant $tenant, TenantStatus $status): Tenant
    {
        $tenant->status = $status;
        $tenant->save();

        return $tenant->refresh();
    }
}
