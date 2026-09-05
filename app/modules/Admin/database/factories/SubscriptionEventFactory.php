<?php

namespace DA\Admin\Database\Factories;

use DA\Admin\Enums\SubscriptionEventType;
use DA\Admin\Enums\SubscriptionStatus;
use DA\Admin\Models\Subscription;
use DA\Admin\Models\SubscriptionEvent;
use DA\Admin\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SubscriptionEvent>
 */
class SubscriptionEventFactory extends Factory
{
    protected $model = SubscriptionEvent::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'subscription_id' => Subscription::factory(),
            'event_type' => SubscriptionEventType::Created,
            'old_status' => null,
            'new_status' => SubscriptionStatus::Active,
            'metadata' => null,
            'occurred_at' => now(),
        ];
    }
}
