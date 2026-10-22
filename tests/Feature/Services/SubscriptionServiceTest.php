<?php

use App\Models\User;
use DA\Admin\DTO\CreateSubscriptionDTO;
use DA\Admin\Enums\Features;
use DA\Admin\Enums\SubscriptionEventType;
use DA\Admin\Enums\SubscriptionItemType;
use DA\Admin\Enums\SubscriptionStatus;
use DA\Admin\Enums\TenantStatus;
use DA\Admin\Exceptions\InvalidSubscriptionTransitionException;
use DA\Admin\Models\Addon;
use DA\Admin\Models\Plan;
use DA\Admin\Models\Subscription;
use DA\Admin\Models\Tenant;
use DA\Admin\Notifications\SubscriptionGraceStarted;
use DA\Admin\Services\SubscriptionService;
use Illuminate\Support\Facades\Notification;

function createSubscriptionDTO(Tenant $tenant, Plan $plan, bool $startTrial = true, array $addonIds = []): CreateSubscriptionDTO
{
    return new CreateSubscriptionDTO(
        tenant_id: $tenant->id,
        plan_id: $plan->id,
        start_trial: $startTrial,
        addon_ids: $addonIds,
    );
}

it('rejects creating a subscription for an inactive tenant', function () {
    $tenant = Tenant::factory()->suspended()->create();
    $plan = Plan::factory()->create();

    expect(fn () => app(SubscriptionService::class)->create(createSubscriptionDTO($tenant, $plan)))
        ->toThrow(InvalidSubscriptionTransitionException::class, 'The selected tenant is suspended.');

    $this->assertDatabaseCount('subscriptions', 0);
    $this->assertDatabaseCount('subscription_events', 0);
});

it('activates an inactive tenant and its super admin when a plan is subscribed', function () {
    $tenant = Tenant::factory()->inactive()->create();
    $plan = Plan::factory()->create();
    $superAdmin = User::factory()->forTenant($tenant)->create([
        'is_super_admin' => true,
        'is_active' => false,
    ]);
    $staff = User::factory()->forTenant($tenant)->create([
        'is_super_admin' => false,
        'is_active' => false,
    ]);

    app(SubscriptionService::class)->create(createSubscriptionDTO($tenant, $plan, startTrial: false));

    $this->assertDatabaseHas('tenants', [
        'id' => $tenant->id,
        'status' => TenantStatus::Active->value,
    ]);
    $this->assertDatabaseHas('users', [
        'id' => $superAdmin->id,
        'is_active' => 1,
    ]);
    $this->assertDatabaseHas('users', [
        'id' => $staff->id,
        'is_active' => 0,
        'is_super_admin' => 0,
    ]);
});

it('copies plan features onto the subscription and keeps later plan edits separate', function () {
    $tenant = Tenant::factory()->create();
    $plan = Plan::factory()->create();
    $plan->planFeatures()->create([
        'feature' => Features::POS->value,
        'value' => '1',
        'is_unlimited' => false,
    ]);

    $subscription = app(SubscriptionService::class)->create(createSubscriptionDTO($tenant, $plan, startTrial: false));

    $this->assertDatabaseHas('subscription_features', [
        'subscription_id' => $subscription->id,
        'feature' => Features::POS->value,
        'value' => '1',
        'is_unlimited' => 0,
    ]);

    $subscription->subscriptionFeatures()->where('feature', Features::POS->value)->update(['value' => '0']);

    expect($plan->planFeatures()->where('feature', Features::POS->value)->value('value'))->toBe('1')
        ->and($subscription->fresh()->hasFeature('pos'))->toBeFalse();

    $plan->planFeatures()->where('feature', Features::POS->value)->update(['value' => '0']);

    expect($subscription->subscriptionFeatures()->where('feature', Features::POS->value)->value('value'))->toBe('0');
});

it('replaces subscription features when the plan changes', function () {
    $tenant = Tenant::factory()->create();
    $current = Plan::factory()->create(['price' => '19.00']);
    $current->planFeatures()->create([
        'feature' => Features::POS->value,
        'value' => '1',
        'is_unlimited' => false,
    ]);
    $next = Plan::factory()->create(['price' => '49.00']);
    $next->planFeatures()->create([
        'feature' => Features::SALES->value,
        'value' => '1',
        'is_unlimited' => false,
    ]);

    $subscription = app(SubscriptionService::class)->create(createSubscriptionDTO($tenant, $current, startTrial: false));

    app(SubscriptionService::class)->changePlan($subscription, $next);

    $this->assertDatabaseMissing('subscription_features', [
        'subscription_id' => $subscription->id,
        'feature' => Features::POS->value,
    ]);
    $this->assertDatabaseHas('subscription_features', [
        'subscription_id' => $subscription->id,
        'feature' => Features::SALES->value,
        'value' => '1',
    ]);
});

it('rejects creating a subscription on an inactive plan', function () {
    $tenant = Tenant::factory()->create();
    $plan = Plan::factory()->inactive()->create();

    expect(fn () => app(SubscriptionService::class)->create(createSubscriptionDTO($tenant, $plan)))
        ->toThrow(InvalidSubscriptionTransitionException::class, 'The selected plan is not active.');

    $this->assertDatabaseCount('subscriptions', 0);
});

it('resumes a paused trial to active when the trial has already ended', function () {
    $subscription = Subscription::factory()->paused()->create([
        'paused_from_status' => SubscriptionStatus::Trialing,
        'trial_ends_at' => now()->subDay(),
    ]);

    $result = app(SubscriptionService::class)->resume($subscription);

    expect($result->status)->toBe(SubscriptionStatus::Active)
        ->and($result->paused_at)->toBeNull()
        ->and($result->paused_from_status)->toBeNull();

    $this->assertDatabaseHas('subscription_events', [
        'subscription_id' => $subscription->id,
        'event_type' => SubscriptionEventType::Resumed->value,
        'old_status' => SubscriptionStatus::Paused->value,
        'new_status' => SubscriptionStatus::Active->value,
    ]);
});

it('records a same-price plan change as PLAN_CHANGED', function () {
    $current = Plan::factory()->create(['name' => 'Starter Monthly', 'price' => '29.00']);
    $next = Plan::factory()->create(['name' => 'Starter Yearly', 'price' => '29.00']);
    $subscription = Subscription::factory()->create(['plan_id' => $current->id]);

    $result = app(SubscriptionService::class)->changePlan($subscription, $next);

    expect($result->plan_id)->toBe($next->id);

    $this->assertDatabaseHas('subscription_events', [
        'subscription_id' => $subscription->id,
        'event_type' => SubscriptionEventType::PlanChanged->value,
    ]);
});

it('rejects upgrade when the selected plan is cheaper', function () {
    $pro = Plan::factory()->create(['price' => '49.00']);
    $starter = Plan::factory()->create(['price' => '19.00']);
    $subscription = Subscription::factory()->create(['plan_id' => $pro->id]);

    expect(fn () => app(SubscriptionService::class)->upgrade($subscription, $starter))
        ->toThrow(InvalidSubscriptionTransitionException::class, 'The selected plan is not an upgrade.');

    $this->assertDatabaseHas('subscriptions', [
        'id' => $subscription->id,
        'plan_id' => $pro->id,
    ]);
});

it('rejects downgrade when the selected plan is more expensive', function () {
    $starter = Plan::factory()->create(['price' => '19.00']);
    $pro = Plan::factory()->create(['price' => '49.00']);
    $subscription = Subscription::factory()->create(['plan_id' => $starter->id]);

    expect(fn () => app(SubscriptionService::class)->downgrade($subscription, $pro))
        ->toThrow(InvalidSubscriptionTransitionException::class, 'The selected plan is not a downgrade.');
});

it('does not expire a subscription that has already ended', function () {
    $subscription = Subscription::factory()->cancelled()->create();

    expect(fn () => app(SubscriptionService::class)->expire($subscription))
        ->toThrow(InvalidSubscriptionTransitionException::class, 'This subscription has already ended.');

    $this->assertDatabaseHas('subscriptions', [
        'id' => $subscription->id,
        'status' => SubscriptionStatus::Cancelled->value,
    ]);
    $this->assertDatabaseMissing('subscription_events', [
        'subscription_id' => $subscription->id,
        'event_type' => SubscriptionEventType::Expired->value,
    ]);
});

it('starts a grace window and notifies when the billing period ends', function () {
    Notification::fake();

    $this->freezeTime();

    $tenant = Tenant::factory()->create([
        'billing_email' => 'billing@harbor.test',
    ]);
    $subscription = Subscription::factory()->create([
        'tenant_id' => $tenant->id,
        'grace_days' => 7,
        'current_period_end' => now()->subMinute(),
    ]);

    $result = app(SubscriptionService::class)->processDuePeriods();

    $subscription->refresh();

    expect($result['grace_started'])->toBe(1)
        ->and($subscription->status)->toBe(SubscriptionStatus::PastDue)
        ->and($subscription->grace_ends_at?->equalTo($subscription->current_period_end->copy()->addDays(7)))->toBeTrue();

    $this->assertDatabaseHas('subscription_events', [
        'subscription_id' => $subscription->id,
        'event_type' => SubscriptionEventType::GraceStarted->value,
    ]);

    Notification::assertSentOnDemand(SubscriptionGraceStarted::class);
});

it('expires a subscription after the grace window ends', function () {
    Notification::fake();

    $this->freezeTime();

    $subscription = Subscription::factory()->create([
        'grace_days' => 7,
        'current_period_end' => now()->subDays(8),
    ]);

    app(SubscriptionService::class)->processDuePeriods();

    expect($subscription->refresh()->status)->toBe(SubscriptionStatus::Expired);

    $this->assertDatabaseHas('subscription_events', [
        'subscription_id' => $subscription->id,
        'event_type' => SubscriptionEventType::Expired->value,
    ]);
});

it('cancels after grace when cancellation was scheduled at period end', function () {
    Notification::fake();

    $this->freezeTime();

    $subscription = Subscription::factory()->create([
        'grace_days' => 7,
        'cancel_at_period_end' => true,
        'current_period_end' => now()->subDays(8),
    ]);

    app(SubscriptionService::class)->processDuePeriods();

    expect($subscription->refresh()->status)->toBe(SubscriptionStatus::Cancelled);
});

it('expires immediately at period end when grace days are zero', function () {
    Notification::fake();

    $this->freezeTime();

    $subscription = Subscription::factory()->create([
        'grace_days' => 0,
        'current_period_end' => now()->subMinute(),
    ]);

    app(SubscriptionService::class)->processDuePeriods();

    expect($subscription->refresh()->status)->toBe(SubscriptionStatus::Expired);
    Notification::assertNothingSent();
});

it('adds an add-on to a current subscription', function () {
    $subscription = Subscription::factory()->create();
    $addon = Addon::factory()->create(['price' => '12.00']);

    $result = app(SubscriptionService::class)->addAddon($subscription, $addon, 2);

    $this->assertDatabaseHas('subscription_items', [
        'subscription_id' => $result->id,
        'item_type' => SubscriptionItemType::Addon->value,
        'reference_id' => $addon->id,
        'quantity' => 2,
        'unit_price' => '12.00',
    ]);
});

it('updates the quantity of an assigned add-on', function () {
    $subscription = Subscription::factory()->create();
    $addon = Addon::factory()->create();

    app(SubscriptionService::class)->addAddon($subscription, $addon, 1);
    app(SubscriptionService::class)->updateAddonQuantity($subscription->refresh(), $addon, 4);

    $this->assertDatabaseHas('subscription_items', [
        'subscription_id' => $subscription->id,
        'item_type' => SubscriptionItemType::Addon->value,
        'reference_id' => $addon->id,
        'quantity' => 4,
    ]);
    $this->assertDatabaseHas('subscription_events', [
        'subscription_id' => $subscription->id,
        'event_type' => SubscriptionEventType::AddonQuantityUpdated->value,
    ]);
});

it('does not add the same add-on twice', function () {
    $subscription = Subscription::factory()->create();
    $addon = Addon::factory()->create();

    app(SubscriptionService::class)->addAddon($subscription, $addon);

    expect(fn () => app(SubscriptionService::class)->addAddon($subscription->refresh(), $addon))
        ->toThrow(InvalidSubscriptionTransitionException::class, 'This add-on is already on the subscription.');
});
