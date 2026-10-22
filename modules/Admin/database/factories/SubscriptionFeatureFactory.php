<?php

namespace DA\Admin\Database\Factories;

use DA\Admin\Enums\Features;
use DA\Admin\Models\Subscription;
use DA\Admin\Models\SubscriptionFeature;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SubscriptionFeature>
 */
class SubscriptionFeatureFactory extends Factory
{
    protected $model = SubscriptionFeature::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'subscription_id' => Subscription::factory(),
            'feature' => Features::PRODUCTS->value,
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
