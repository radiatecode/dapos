<?php

namespace DA\Admin\Services\Entitlements;

use DA\Admin\Models\Tenant;

class ResourceUsageRegistry
{
    /**
     * @var array<string, ResourceUsageCounter>
     */
    private array $counters = [];

    /**
     * @param  iterable<ResourceUsageCounter>  $counters
     */
    public function __construct(iterable $counters = [])
    {
        foreach ($counters as $counter) {
            $this->register($counter);
        }
    }

    public function register(ResourceUsageCounter $counter): void
    {
        $this->counters[$counter->featureCode()] = $counter;
    }

    public function has(string $code): bool
    {
        return isset($this->counters[$code]);
    }

    public function count(string $code, Tenant $tenant): int
    {
        if (! isset($this->counters[$code])) {
            return 0;
        }

        return $this->counters[$code]->count($tenant);
    }
}
