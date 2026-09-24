<?php

namespace DA\Admin\Database\Factories;

use DA\Admin\Enums\SubscriptionStatus;
use DA\Admin\Models\Plan;
use DA\Admin\Models\Subscription;
use DA\Admin\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subscription>
 */
class SubscriptionFactory extends Factory
{
    protected $model = Subscription::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startsAt = now()->startOfSecond();

        return [
            'tenant_id' => Tenant::factory(),
            'plan_id' => Plan::factory(),
            'status' => SubscriptionStatus::Active,
            'starts_at' => $startsAt,
            'trial_ends_at' => null,
            'current_period_start' => $startsAt,
            'current_period_end' => $startsAt->copy()->addMonth(),
            'cancel_at_period_end' => false,
            'grace_days' => Subscription::DEFAULT_GRACE_DAYS,
            'grace_ends_at' => null,
            'grace_notified_at' => null,
            'cancelled_at' => null,
            'ended_at' => null,
            'paused_at' => null,
            'paused_from_status' => null,
        ];
    }

    public function trialing(): static
    {
        return $this->state(function (array $attributes): array {
            $startsAt = $attributes['starts_at'] ?? now()->startOfSecond();

            return [
                'status' => SubscriptionStatus::Trialing,
                'trial_ends_at' => $startsAt->copy()->addDays(14),
                'current_period_start' => $startsAt,
                'current_period_end' => $startsAt->copy()->addDays(14),
            ];
        });
    }

    public function paused(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => SubscriptionStatus::Paused,
            'paused_at' => now(),
            'paused_from_status' => SubscriptionStatus::Active,
        ]);
    }

    public function pastDue(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => SubscriptionStatus::PastDue,
            'grace_ends_at' => now()->addDays($attributes['grace_days'] ?? Subscription::DEFAULT_GRACE_DAYS),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => SubscriptionStatus::Cancelled,
            'cancelled_at' => now(),
            'ended_at' => now(),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => SubscriptionStatus::Expired,
            'ended_at' => now(),
        ]);
    }
}
