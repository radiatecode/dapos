<?php

namespace Tests\Fixtures;

use DA\Admin\Models\Tenant;
use DA\Admin\Services\Entitlements\ResourceUsageCounter;

class FixedResourceUsageCounter implements ResourceUsageCounter
{
    public function __construct(
        private string $code,
        private int $count,
    ) {}

    public function featureCode(): string
    {
        return $this->code;
    }

    public function count(Tenant $tenant): int
    {
        return $this->count;
    }
}
