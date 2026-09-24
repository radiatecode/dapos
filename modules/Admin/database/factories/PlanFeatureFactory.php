<?php

namespace DA\Admin\Database\Factories;

use DA\Admin\Models\Feature;
use DA\Admin\Models\Plan;
use DA\Admin\Models\PlanFeature;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlanFeature>
 */
class PlanFeatureFactory extends Factory
{
    protected $model = PlanFeature::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'plan_id' => Plan::factory(),
            'feature_id' => Feature::factory(),
            'value' => '5',
            'is_unlimited' => false,
        ];
    }

    public function unlimited(): static
    {
        return $this->state(fn (array $attributes): array => [
            'value' => null,
            'is_unlimited' => true,
        ]);
    }

    public function enabled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'value' => '1',
            'is_unlimited' => false,
        ]);
    }

    public function disabled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'value' => '0',
            'is_unlimited' => false,
        ]);
    }
}
