<?php

namespace DA\Admin\Services\Entitlements;

use DA\Admin\Models\Tenant;

interface ResourceUsageCounter
{
    public function featureCode(): string;

    public function count(Tenant $tenant): int;
}
