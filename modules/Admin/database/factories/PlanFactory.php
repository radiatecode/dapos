<?php

namespace DA\Admin\Database\Factories;

use DA\Admin\Enums\BillingInterval;
use DA\Admin\Models\Currency;
use DA\Admin\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Plan>
 */
class PlanFactory extends Factory
{
    protected $model = Plan::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name' => Str::title($name),
            'code' => Str::slug($name).'-'.fake()->unique()->numerify('###'),
            'description' => fake()->sentence(),
            'billing_interval' => BillingInterval::Monthly,
            'price' => '19.00',
            'currency_id' => Currency::factory(),
            'trial_days' => 14,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }

    public function yearly(): static
    {
        return $this->state(fn (array $attributes): array => [
            'billing_interval' => BillingInterval::Yearly,
        ]);
    }
}
