<?php

use DA\Admin\Enums\Features;
use DA\Admin\Models\Subscription;
use DA\Admin\Models\Tenant;
use DA\Admin\Models\TenantUsage;
use DA\Admin\Services\UsageService;

it('increments usage for the current subscription period', function () {
    $feature = Features::PRODUCTS->value;
    $subscription = Subscription::factory()->create();

    $row = app(UsageService::class)->increment($subscription, $feature, 2);

    expect($row->usage)->toBe(2)
        ->and($row->tenant_id)->toBe($subscription->tenant_id)
        ->and($row->feature)->toBe($feature)
        ->and($row->period_start->equalTo($subscription->current_period_start))->toBeTrue()
        ->and($row->period_end->equalTo($subscription->current_period_end))->toBeTrue();

    expect(app(UsageService::class)->current($subscription, $feature))->toBe(2);
});

it('does not decrement usage below zero', function () {
    $feature = Features::PRODUCTS->value;
    $subscription = Subscription::factory()->create();

    app(UsageService::class)->increment($subscription, $feature, 1);
    $row = app(UsageService::class)->decrement($subscription, $feature, 5);

    expect($row->usage)->toBe(0)
        ->and(app(UsageService::class)->current($subscription, $feature))->toBe(0);
});

it('isolates usage to the subscription period', function () {
    $feature = Features::PRODUCTS->value;
    $subscription = Subscription::factory()->create();

    app(UsageService::class)->increment($subscription, $feature, 3);

    $nextStart = $subscription->current_period_end->copy()->addSecond();
    $subscription->update([
        'current_period_start' => $nextStart,
        'current_period_end' => $nextStart->copy()->addMonth(),
    ]);

    $subscription = $subscription->fresh();

    expect(app(UsageService::class)->current($subscription, $feature))->toBe(0);

    app(UsageService::class)->increment($subscription, $feature);

    expect(TenantUsage::query()->count())->toBe(2)
        ->and(app(UsageService::class)->current($subscription, $feature))->toBe(1);
});

it('scopes usage rows to the owning tenant and feature', function () {
    $products = Features::PRODUCTS->value;
    $roles = Features::ROLES->value;
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();
    $subscriptionA = Subscription::factory()->create(['tenant_id' => $tenantA->id]);
    $subscriptionB = Subscription::factory()->create(['tenant_id' => $tenantB->id]);

    app(UsageService::class)->increment($subscriptionA, $products, 4);
    app(UsageService::class)->increment($subscriptionA, $roles, 1);
    app(UsageService::class)->increment($subscriptionB, $products, 2);

    expect(app(UsageService::class)->current($subscriptionA, $products))->toBe(4)
        ->and(app(UsageService::class)->current($subscriptionA, $roles))->toBe(1)
        ->and(app(UsageService::class)->current($subscriptionB, $products))->toBe(2);
});
