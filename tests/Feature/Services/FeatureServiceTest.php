<?php

use App\Models\User;
use DA\Admin\Enums\Features;
use DA\Admin\Enums\SubscriptionStatus;
use DA\Admin\Exceptions\FeatureUnavailableException;
use DA\Admin\Exceptions\LimitExceededException;
use DA\Admin\Exceptions\SubscriptionExpiredException;
use DA\Admin\Exceptions\SubscriptionMissingException;
use DA\Admin\Models\Plan;
use DA\Admin\Models\Subscription;
use DA\Admin\Models\Tenant;
use DA\Admin\Services\Entitlements\ResourceUsageRegistry;
use DA\Admin\Services\Entitlements\UsersResourceCounter;
use DA\Admin\Services\FeatureService;
use Tests\Fixtures\FixedResourceUsageCounter;

/**
 * @param  list<array{feature: string, value?: string|null, is_unlimited?: bool}>  $assignments
 * @param  array<string, mixed>  $subscriptionAttributes
 * @return array{0: Tenant, 1: Subscription}
 */
function tenantWithEntitlements(array $assignments = [], array $subscriptionAttributes = []): array
{
    $tenant = Tenant::factory()->create();
    $plan = Plan::factory()->create();

    $subscription = Subscription::factory()->create([
        ...$subscriptionAttributes,
        'tenant_id' => $tenant->id,
        'plan_id' => $plan->id,
    ]);

    foreach ($assignments as $assignment) {
        $subscription->subscriptionFeatures()->create([
            'feature' => $assignment['feature'],
            'value' => $assignment['value'] ?? '1',
            'is_unlimited' => $assignment['is_unlimited'] ?? false,
        ]);
    }

    return [$tenant->fresh(), $subscription->fresh()];
}

function createUsersForTenant(Tenant $tenant, int $count = 1): void
{
    User::factory()
        ->count($count)
        ->make()
        ->each(function (User $user) use ($tenant): void {
            unset($user->role);
            $user->tenant_id = $tenant->id;
            $user->save();
        });
}

it('allows a boolean feature when the plan enables it', function () {
    [$tenant] = tenantWithEntitlements([
        ['feature' => Features::POS->value, 'value' => '1'],
    ]);

    expect($tenant->subscription->can('pos'))->toBeTrue()
        ->and($tenant->subscription->hasFeature('pos'))->toBeTrue();
});

it('denies a boolean feature when the plan disables it', function () {
    [$tenant] = tenantWithEntitlements([
        ['feature' => Features::POS->value, 'value' => '0'],
    ]);

    expect($tenant->subscription->can('pos'))->toBeFalse()
        ->and($tenant->subscription->hasFeature('pos'))->toBeFalse();

    expect(fn () => $tenant->subscription->consume('pos'))
        ->toThrow(FeatureUnavailableException::class);
});

it('returns the numeric plan limit for a resource feature', function () {
    [$tenant] = tenantWithEntitlements([
        ['feature' => Features::STORES->value, 'value' => '5'],
    ]);

    expect($tenant->subscription->limit('stores'))->toBe(5)
        ->and($tenant->subscription->isUnlimited('stores'))->toBeFalse()
        ->and($tenant->subscription->can('stores'))->toBeTrue();
});

it('treats an unlimited limit as always consumable', function () {
    [$tenant] = tenantWithEntitlements([
        ['feature' => Features::PRODUCTS->value, 'value' => null, 'is_unlimited' => true],
    ]);

    expect($tenant->subscription->isUnlimited('products'))->toBeTrue()
        ->and($tenant->subscription->limit('products'))->toBeNull()
        ->and($tenant->subscription->canConsume('products', 100))->toBeTrue();

    $tenant->subscription->consume('products', 100);

    expect($tenant->subscription->canConsume('products'))->toBeTrue();
});

it('denies creating another store when the resource limit is already reached', function () {
    [$tenant] = tenantWithEntitlements([
        ['feature' => Features::STORES->value, 'value' => '3'],
    ]);

    $this->app->instance(ResourceUsageRegistry::class, new ResourceUsageRegistry([
        new UsersResourceCounter,
        new FixedResourceUsageCounter('stores', 3),
    ]));

    expect($tenant->subscription->limit('stores'))->toBe(3)
        ->and($tenant->subscription->can('stores'))->toBeFalse()
        ->and($tenant->subscription->canConsume('stores'))->toBeFalse();

    expect(fn () => $tenant->subscription->consume('stores'))
        ->toThrow(LimitExceededException::class, 'The plan limit for stores has been reached.');

    $this->assertDatabaseCount('tenant_usages', 0);
});

it('counts tenant users from actual user records', function () {
    [$tenant] = tenantWithEntitlements([
        ['feature' => Features::USERS->value, 'value' => '3'],
    ]);

    createUsersForTenant($tenant, 3);
    createUsersForTenant(Tenant::factory()->create(), 1);

    expect(app(FeatureService::class)->currentUsage($tenant, 'users'))->toBe(3)
        ->and($tenant->subscription->canConsume('users'))->toBeFalse();

    expect(fn () => $tenant->subscription->consume('users'))
        ->toThrow(LimitExceededException::class);
});

it('consumes and releases period-scoped product usage', function () {
    [$tenant] = tenantWithEntitlements([
        ['feature' => Features::PRODUCTS->value, 'value' => '2'],
    ]);

    $tenant->subscription->consume('products');
    $tenant->subscription->consume('products');

    expect($tenant->subscription->canConsume('products'))->toBeFalse()
        ->and(app(FeatureService::class)->currentUsage($tenant, 'products'))->toBe(2);

    expect(fn () => $tenant->subscription->consume('products'))
        ->toThrow(LimitExceededException::class);

    $tenant->subscription->release('products');

    expect($tenant->subscription->canConsume('products'))->toBeTrue()
        ->and(app(FeatureService::class)->currentUsage($tenant, 'products'))->toBe(1);

    $tenant->subscription->consume('products');

    expect(app(FeatureService::class)->currentUsage($tenant, 'products'))->toBe(2);
});

it('does not count previous period usage toward the current period', function () {
    [$tenant, $subscription] = tenantWithEntitlements([
        ['feature' => Features::PRODUCTS->value, 'value' => '1'],
    ]);

    $subscription->consume('products');

    expect($subscription->canConsume('products'))->toBeFalse();

    $nextStart = $subscription->current_period_end->copy()->addSecond();
    $subscription->update([
        'current_period_start' => $nextStart,
        'current_period_end' => $nextStart->copy()->addMonth(),
    ]);

    $subscription = $subscription->fresh();

    expect($subscription->canConsume('products'))->toBeTrue()
        ->and(app(FeatureService::class)->currentUsage($subscription, 'products'))->toBe(0);

    $this->assertDatabaseCount('tenant_usages', 1);
});

it('rejects consume when the feature is not on the plan', function () {
    [$tenant] = tenantWithEntitlements();

    expect($tenant->subscription->hasFeature('advanced_reports'))->toBeFalse()
        ->and($tenant->subscription->can('advanced_reports'))->toBeFalse()
        ->and($tenant->subscription->limit('stores'))->toBe(0);

    expect(fn () => $tenant->subscription->consume('advanced_reports'))
        ->toThrow(FeatureUnavailableException::class);
});

it('rejects entitlement checks when the tenant has no subscription', function () {
    $tenant = Tenant::factory()->create();

    expect(fn () => app(FeatureService::class)->can($tenant, 'pos'))
        ->toThrow(SubscriptionMissingException::class);
});

it('rejects entitlement checks when the subscription has expired', function () {
    [$tenant] = tenantWithEntitlements(
        [['feature' => Features::POS->value, 'value' => '1']],
        [
            'status' => SubscriptionStatus::Expired,
            'ended_at' => now(),
        ],
    );

    expect($tenant->subscription)->toBeNull();

    expect(fn () => app(FeatureService::class)->can($tenant, 'pos'))
        ->toThrow(SubscriptionExpiredException::class);
});

it('rejects entitlement checks when the subscription is cancelled', function () {
    $plan = Plan::factory()->create();
    $plan->planFeatures()->create([
        'feature' => Features::POS->value,
        'value' => '1',
        'is_unlimited' => false,
    ]);

    $subscription = Subscription::factory()->cancelled()->create([
        'plan_id' => $plan->id,
    ]);

    expect(fn () => $subscription->can('pos'))
        ->toThrow(SubscriptionExpiredException::class);
});

it('allows entitlement checks while a subscription is in grace', function () {
    [$tenant] = tenantWithEntitlements(
        [['feature' => Features::POS->value, 'value' => '1']],
        [
            'status' => SubscriptionStatus::PastDue,
            'grace_ends_at' => now()->addDays(7),
        ],
    );

    expect($tenant->subscription->can('pos'))->toBeTrue();
});

it('does not increment another tenant usage when consuming', function () {
    [$tenantA] = tenantWithEntitlements([
        ['feature' => Features::PRODUCTS->value, 'value' => '5'],
    ]);
    [$tenantB] = tenantWithEntitlements([
        ['feature' => Features::PRODUCTS->value, 'value' => '5'],
    ]);

    $tenantA->subscription->consume('products', 2);

    expect(app(FeatureService::class)->currentUsage($tenantA, 'products'))->toBe(2)
        ->and(app(FeatureService::class)->currentUsage($tenantB, 'products'))->toBe(0);
});
