<?php

namespace DA\Admin\Models\Queries;

use DA\Admin\Models\TenantUsage;
use DateTimeInterface;

class TenantUsageQueries extends BaseQueries
{
    public function forTenantFeaturePeriod(int $tenantId, int $featureId, DateTimeInterface $periodStart): ?TenantUsage
    {
        return $this->eloquentBuilder()
            ->where('tenant_id', $tenantId)
            ->where('feature_id', $featureId)
            ->where('period_start', $periodStart)
            ->first();
    }
}
