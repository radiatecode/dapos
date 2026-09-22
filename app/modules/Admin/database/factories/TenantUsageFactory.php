<?php

namespace DA\Admin\Database\Factories;

use DA\Admin\Models\Feature;
use DA\Admin\Models\Tenant;
use DA\Admin\Models\TenantUsage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TenantUsage>
 */
class TenantUsageFactory extends Factory
{
    protected $model = TenantUsage::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $periodStart = now()->startOfMonth();

        return [
            'tenant_id' => Tenant::factory(),
            'feature_id' => Feature::factory()->consumption(),
            'usage' => 0,
            'period_start' => $periodStart,
            'period_end' => $periodStart->copy()->endOfMonth(),
        ];
    }
}
