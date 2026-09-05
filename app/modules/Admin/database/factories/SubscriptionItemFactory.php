<?php

namespace DA\Admin\Database\Factories;

use DA\Admin\Enums\SubscriptionItemType;
use DA\Admin\Models\Plan;
use DA\Admin\Models\Subscription;
use DA\Admin\Models\SubscriptionItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SubscriptionItem>
 */
class SubscriptionItemFactory extends Factory
{
    protected $model = SubscriptionItem::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'subscription_id' => Subscription::factory(),
            'item_type' => SubscriptionItemType::Plan,
            'reference_id' => Plan::factory(),
            'quantity' => 1,
            'unit_price' => '19.00',
        ];
    }

    public function addon(int $addonId, string $unitPrice = '9.00'): static
    {
        return $this->state(fn (array $attributes): array => [
            'item_type' => SubscriptionItemType::Addon,
            'reference_id' => $addonId,
            'unit_price' => $unitPrice,
        ]);
    }
}
