<?php

namespace DA\Admin\Models\Queries;

use DA\Admin\Models\TenantUsage;
use DateTimeInterface;

class TenantUsageQueries extends BaseQueries
{
    public function forTenantFeaturePeriod(int $tenantId, string $feature, DateTimeInterface $periodStart): ?TenantUsage
    {
        return $this->eloquentBuilder()
            ->where('tenant_id', $tenantId)
            ->where('feature', $feature)
            ->where('period_start', $periodStart)
            ->first();
    }
}
