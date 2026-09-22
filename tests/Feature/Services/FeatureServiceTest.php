<?php

use App\Models\User;
use DA\Admin\Enums\SubscriptionStatus;
use DA\Admin\Exceptions\FeatureUnavailableException;
use DA\Admin\Exceptions\LimitExceededException;
use DA\Admin\Exceptions\SubscriptionExpiredException;
use DA\Admin\Exceptions\SubscriptionMissingException;
use DA\Admin\Models\Feature;
use DA\Admin\Models\Plan;
use DA\Admin\Models\Subscription;
use DA\Admin\Models\Tenant;
use DA\Admin\Services\Entitlements\ResourceUsageRegistry;
use DA\Admin\Services\Entitlements\UsersResourceCounter;
use DA\Admin\Services\FeatureService;
use Tests\Fixtures\FixedResourceUsageCounter;

/**
 * @param  list<array{feature: Feature, value?: string|null, is_unlimited?: bool}>  $assignments
 * @return array{0: Tenant, 1: Subscription}
 */
function tenantWithEntitlements(array $assignments = [], array $subscriptionAttributes = []): array
{
    $tenant = Tenant::factory()->create();
    $plan = Plan::factory()->create();

    foreach ($assignments as $assignment) {
        $plan->features()->attach($assignment['feature']->id, [
            'value' => $assignment['value'] ?? '1',
            'is_unlimited' => $assignment['is_unlimited'] ?? false,
        ]);
    }

    $subscription = Subscription::factory()->create([
        ...$subscriptionAttributes,
        'tenant_id' => $tenant->id,
        'plan_id' => $plan->id,
    ]);

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
    $feature = Feature::factory()->boolean()->create(['code' => 'multi_location']);
    [$tenant] = tenantWithEntitlements([
        ['feature' => $feature, 'value' => '1'],
    ]);

    expect($tenant->subscription->can('multi_location'))->toBeTrue()
        ->and($tenant->subscription->hasFeature('multi_location'))->toBeTrue();
});

it('denies a boolean feature when the plan disables it', function () {
    $feature = Feature::factory()->boolean()->create(['code' => 'multi_location']);
    [$tenant] = tenantWithEntitlements([
        ['feature' => $feature, 'value' => '0'],
    ]);

    expect($tenant->subscription->can('multi_location'))->toBeFalse()
        ->and($tenant->subscription->hasFeature('multi_location'))->toBeFalse();

    expect(fn () => $tenant->subscription->consume('multi_location'))
        ->toThrow(FeatureUnavailableException::class);
});

it('returns the numeric plan limit for a resource feature', function () {
    $feature = Feature::factory()->resource()->create(['code' => 'locations']);
    [$tenant] = tenantWithEntitlements([
        ['feature' => $feature, 'value' => '5'],
    ]);

    expect($tenant->subscription->limit('locations'))->toBe(5)
        ->and($tenant->subscription->isUnlimited('locations'))->toBeFalse()
        ->and($tenant->subscription->can('locations'))->toBeTrue();
});

it('treats an unlimited limit as always consumable', function () {
    $feature = Feature::factory()->consumption()->create(['code' => 'invoices']);
    [$tenant] = tenantWithEntitlements([
        ['feature' => $feature, 'value' => null, 'is_unlimited' => true],
    ]);

    expect($tenant->subscription->isUnlimited('invoices'))->toBeTrue()
        ->and($tenant->subscription->limit('invoices'))->toBeNull()
        ->and($tenant->subscription->canConsume('invoices', 100))->toBeTrue();

    $tenant->subscription->consume('invoices', 100);

    expect($tenant->subscription->canConsume('invoices'))->toBeTrue();
});

it('denies creating another location when the resource limit is already reached', function () {
    $feature = Feature::factory()->resource()->create(['code' => 'locations']);
    [$tenant] = tenantWithEntitlements([
        ['feature' => $feature, 'value' => '3'],
    ]);

    $this->app->instance(ResourceUsageRegistry::class, new ResourceUsageRegistry([
        new UsersResourceCounter,
        new FixedResourceUsageCounter('locations', 3),
    ]));

    expect($tenant->subscription->limit('locations'))->toBe(3)
        ->and($tenant->subscription->can('locations'))->toBeFalse()
        ->and($tenant->subscription->canConsume('locations'))->toBeFalse();

    expect(fn () => $tenant->subscription->consume('locations'))
        ->toThrow(LimitExceededException::class, 'The plan limit for locations has been reached.');

    $this->assertDatabaseCount('tenant_usages', 0);
});

it('counts tenant users from actual user records', function () {
    $feature = Feature::factory()->resource()->create(['code' => 'users']);
    [$tenant] = tenantWithEntitlements([
        ['feature' => $feature, 'value' => '3'],
    ]);

    createUsersForTenant($tenant, 3);
    createUsersForTenant(Tenant::factory()->create(), 1);

    expect(app(FeatureService::class)->currentUsage($tenant, 'users'))->toBe(3)
        ->and($tenant->subscription->canConsume('users'))->toBeFalse();

    expect(fn () => $tenant->subscription->consume('users'))
        ->toThrow(LimitExceededException::class);
});

it('consumes and releases period-scoped invoice usage', function () {
    $feature = Feature::factory()->consumption()->create(['code' => 'invoices']);
    [$tenant] = tenantWithEntitlements([
        ['feature' => $feature, 'value' => '2'],
    ]);

    $tenant->subscription->consume('invoices');
    $tenant->subscription->consume('invoices');

    expect($tenant->subscription->canConsume('invoices'))->toBeFalse()
        ->and(app(FeatureService::class)->currentUsage($tenant, 'invoices'))->toBe(2);

    expect(fn () => $tenant->subscription->consume('invoices'))
        ->toThrow(LimitExceededException::class);

    $tenant->subscription->release('invoices');

    expect($tenant->subscription->canConsume('invoices'))->toBeTrue()
        ->and(app(FeatureService::class)->currentUsage($tenant, 'invoices'))->toBe(1);

    $tenant->subscription->consume('invoices');

    expect(app(FeatureService::class)->currentUsage($tenant, 'invoices'))->toBe(2);
});

it('does not count previous period usage toward the current period', function () {
    $feature = Feature::factory()->consumption()->create(['code' => 'invoices']);
    [$tenant, $subscription] = tenantWithEntitlements([
        ['feature' => $feature, 'value' => '1'],
    ]);

    $subscription->consume('invoices');

    expect($subscription->canConsume('invoices'))->toBeFalse();

    $nextStart = $subscription->current_period_end->copy()->addSecond();
    $subscription->update([
        'current_period_start' => $nextStart,
        'current_period_end' => $nextStart->copy()->addMonth(),
    ]);

    $subscription = $subscription->fresh();

    expect($subscription->canConsume('invoices'))->toBeTrue()
        ->and(app(FeatureService::class)->currentUsage($subscription, 'invoices'))->toBe(0);

    $this->assertDatabaseCount('tenant_usages', 1);
});

it('rejects consume when the feature is not on the plan', function () {
    Feature::factory()->boolean()->create(['code' => 'advanced_reports']);
    [$tenant] = tenantWithEntitlements();

    expect($tenant->subscription->hasFeature('advanced_reports'))->toBeFalse()
        ->and($tenant->subscription->can('advanced_reports'))->toBeFalse()
        ->and($tenant->subscription->limit('locations'))->toBe(0);

    expect(fn () => $tenant->subscription->consume('advanced_reports'))
        ->toThrow(FeatureUnavailableException::class);
});

it('rejects entitlement checks when the tenant has no subscription', function () {
    $tenant = Tenant::factory()->create();

    expect(fn () => app(FeatureService::class)->can($tenant, 'multi_location'))
        ->toThrow(SubscriptionMissingException::class);
});

it('rejects entitlement checks when the subscription has expired', function () {
    $feature = Feature::factory()->boolean()->create(['code' => 'multi_location']);
    [$tenant] = tenantWithEntitlements(
        [['feature' => $feature, 'value' => '1']],
        [
            'status' => SubscriptionStatus::Expired,
            'ended_at' => now(),
        ],
    );

    expect($tenant->subscription)->toBeNull();

    expect(fn () => app(FeatureService::class)->can($tenant, 'multi_location'))
        ->toThrow(SubscriptionExpiredException::class);
});

it('rejects entitlement checks when the subscription is cancelled', function () {
    $feature = Feature::factory()->boolean()->create(['code' => 'multi_location']);
    $plan = Plan::factory()->create();
    $plan->features()->attach($feature->id, ['value' => '1', 'is_unlimited' => false]);

    $subscription = Subscription::factory()->cancelled()->create([
        'plan_id' => $plan->id,
    ]);

    expect(fn () => $subscription->can('multi_location'))
        ->toThrow(SubscriptionExpiredException::class);
});

it('allows entitlement checks while a subscription is in grace', function () {
    $feature = Feature::factory()->boolean()->create(['code' => 'multi_location']);
    [$tenant] = tenantWithEntitlements(
        [['feature' => $feature, 'value' => '1']],
        [
            'status' => SubscriptionStatus::PastDue,
            'grace_ends_at' => now()->addDays(7),
        ],
    );

    expect($tenant->subscription->can('multi_location'))->toBeTrue();
});

it('does not increment another tenant usage when consuming', function () {
    $feature = Feature::factory()->consumption()->create(['code' => 'invoices']);
    [$tenantA] = tenantWithEntitlements([
        ['feature' => $feature, 'value' => '5'],
    ]);
    [$tenantB] = tenantWithEntitlements([
        ['feature' => $feature, 'value' => '5'],
    ]);

    $tenantA->subscription->consume('invoices', 2);

    expect(app(FeatureService::class)->currentUsage($tenantA, 'invoices'))->toBe(2)
        ->and(app(FeatureService::class)->currentUsage($tenantB, 'invoices'))->toBe(0);
});
