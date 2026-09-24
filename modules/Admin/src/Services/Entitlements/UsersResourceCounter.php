<?php

namespace DA\Admin\Services\Entitlements;

use App\Models\User;
use DA\Admin\Models\Tenant;

class UsersResourceCounter implements ResourceUsageCounter
{
    public function featureCode(): string
    {
        return 'users';
    }

    public function count(Tenant $tenant): int
    {
        return User::query()
            ->where('tenant_id', $tenant->id)
            ->count();
    }
}
