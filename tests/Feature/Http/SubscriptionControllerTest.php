<?php

use DA\Admin\Enums\AdminPermission;
use DA\Admin\Enums\SubscriptionEventType;
use DA\Admin\Enums\SubscriptionItemType;
use DA\Admin\Enums\SubscriptionStatus;
use DA\Admin\Models\Addon;
use DA\Admin\Models\Plan;
use DA\Admin\Models\Subscription;
use DA\Admin\Models\Tenant;

describe('index', function () {
    it('redirects guests from the subscription list to login', function () {
        $this->get(route('admin.subscriptions.index'))
            ->assertRedirect(route('admin.login'));
    });

    it('forbids an admin without subscriptions.view', function () {
        actingAsPlatformAdmin([AdminPermission::PlansView]);

        $this->get(route('admin.subscriptions.index'))->assertForbidden();
    });

    it('renders the subscription datatable for a platform admin', function () {
        actingAsPlatformAdmin();

        $this->get(route('admin.subscriptions.index'))
            ->assertOk()
            ->assertSee('subscriptions-table', false)
            ->assertSee('dataTables.buttons', false)
            ->assertSee('"extend":"create"', false);
    });
});

describe('create', function () {
    it('redirects guests from the create page to login', function () {
        $this->get(route('admin.subscriptions.create'))
            ->assertRedirect(route('admin.login'));
    });

    it('forbids an admin without subscriptions.create', function () {
        actingAsPlatformAdmin([AdminPermission::SubscriptionsView]);

        $this->get(route('admin.subscriptions.create'))->assertForbidden();
    });

    it('renders the create form for an authorized admin', function () {
        actingAsPlatformAdmin();

        $tenant = Tenant::factory()->create(['name' => 'Harbor Cafe']);
        Tenant::factory()->suspended()->create(['name' => 'Closed Shop']);
        $plan = Plan::factory()->create(['name' => 'Starter', 'trial_days' => 14]);

        $this->get(route('admin.subscriptions.create'))
            ->assertOk()
            ->assertSee('Create subscription')
            ->assertSee('name="tenant_id"', false)
            ->assertSee('name="plan_id"', false)
            ->assertSee('name="start_trial"', false)
            ->assertSee('name="grace_days"', false)
            ->assertSee('Harbor Cafe')
            ->assertSee('Starter')
            ->assertSee((string) $tenant->id, false)
            ->assertSee((string) $plan->id, false)
            ->assertDontSee('Closed Shop');
    });
});

describe('store', function () {
    it('redirects guests away from subscription creation', function () {
        $this->post(route('admin.subscriptions.store'), subscriptionPayload())
            ->assertRedirect(route('admin.login'));

        $this->assertDatabaseCount('subscriptions', 0);
    });

    it('forbids an admin without subscriptions.create from storing', function () {
        actingAsPlatformAdmin([AdminPermission::SubscriptionsView]);

        $this->post(route('admin.subscriptions.store'), subscriptionPayload())
            ->assertForbidden();

        $this->assertDatabaseCount('subscriptions', 0);
    });

    it('creates a trialing subscription with a plan item and events', function () {
        actingAsPlatformAdmin();

        $tenant = Tenant::factory()->create();
        $plan = Plan::factory()->create(['trial_days' => 14, 'price' => '19.00']);
        $addon = Addon::factory()->create(['price' => '9.00']);

        $this->from(route('admin.subscriptions.create'))
            ->post(route('admin.subscriptions.store'), subscriptionPayload([
                'tenant_id' => $tenant->id,
                'plan_id' => $plan->id,
                'start_trial' => '1',
                'addon_ids' => [$addon->id],
            ]))
            ->assertRedirect();

        $subscription = Subscription::query()->where('tenant_id', $tenant->id)->first();

        expect($subscription)->not->toBeNull()
            ->and($subscription->status)->toBe(SubscriptionStatus::Trialing)
            ->and($subscription->plan_id)->toBe($plan->id)
            ->and($subscription->trial_ends_at)->not->toBeNull()
            ->and($subscription->grace_days)->toBe(7);

        $this->assertDatabaseHas('subscription_items', [
            'subscription_id' => $subscription->id,
            'item_type' => SubscriptionItemType::Plan->value,
            'reference_id' => $plan->id,
            'unit_price' => '19.00',
        ]);
        $this->assertDatabaseHas('subscription_items', [
            'subscription_id' => $subscription->id,
            'item_type' => SubscriptionItemType::Addon->value,
            'reference_id' => $addon->id,
        ]);
        $this->assertDatabaseHas('subscription_events', [
            'subscription_id' => $subscription->id,
            'event_type' => SubscriptionEventType::Created->value,
            'new_status' => SubscriptionStatus::Trialing->value,
        ]);
        $this->assertDatabaseHas('subscription_events', [
            'subscription_id' => $subscription->id,
            'event_type' => SubscriptionEventType::TrialStarted->value,
        ]);
    });

    it('creates an active subscription when trial is not requested', function () {
        actingAsPlatformAdmin();

        $tenant = Tenant::factory()->create();
        $plan = Plan::factory()->create(['trial_days' => 14]);

        $this->post(route('admin.subscriptions.store'), subscriptionPayload([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'start_trial' => '0',
        ]))->assertRedirect();

        $this->assertDatabaseHas('subscriptions', [
            'tenant_id' => $tenant->id,
            'status' => SubscriptionStatus::Active->value,
            'grace_days' => 7,
        ]);
        $this->assertDatabaseHas('subscription_events', [
            'tenant_id' => $tenant->id,
            'event_type' => SubscriptionEventType::Activated->value,
        ]);
    });

    it('returns a validation error when the tenant is missing', function () {
        actingAsPlatformAdmin();

        $this->from(route('admin.subscriptions.create'))
            ->post(route('admin.subscriptions.store'), subscriptionPayload(['tenant_id' => '']))
            ->assertRedirect(route('admin.subscriptions.create'))
            ->assertSessionHasErrors(['tenant_id']);

        $this->assertDatabaseCount('subscriptions', 0);
    });

    it('rejects a second current subscription for the same tenant', function () {
        actingAsPlatformAdmin();

        $tenant = Tenant::factory()->create();
        $plan = Plan::factory()->create(['trial_days' => 14]);
        Subscription::factory()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
        ]);

        $this->from(route('admin.subscriptions.create'))
            ->post(route('admin.subscriptions.store'), subscriptionPayload([
                'tenant_id' => $tenant->id,
                'plan_id' => $plan->id,
            ]))
            ->assertRedirect(route('admin.subscriptions.create'));

        $this->assertDatabaseCount('subscriptions', 1);
    });
});

describe('show', function () {
    it('redirects guests from the details page to login', function () {
        $subscription = Subscription::factory()->create();

        $this->get(route('admin.subscriptions.show', $subscription))
            ->assertRedirect(route('admin.login'));
    });

    it('forbids an admin without subscriptions.view from viewing details', function () {
        actingAsPlatformAdmin([AdminPermission::PlansView]);

        $subscription = Subscription::factory()->create();

        $this->get(route('admin.subscriptions.show', $subscription))->assertForbidden();
    });

    it('renders tenant, plan, status, and history for a platform admin', function () {
        actingAsPlatformAdmin();

        $tenant = Tenant::factory()->create(['name' => 'Harbor Cafe']);
        $plan = Plan::factory()->create(['name' => 'Starter']);
        Addon::factory()->create(['name' => 'Extra location']);
        $subscription = Subscription::factory()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
        ]);

        $this->get(route('admin.subscriptions.show', $subscription))
            ->assertOk()
            ->assertSee('Harbor Cafe')
            ->assertSee('Starter')
            ->assertSee('Active')
            ->assertSee('Subscription history')
            ->assertSee('Plan change history')
            ->assertSee('Grace days')
            ->assertSee('Add-ons')
            ->assertSee('Add add-on')
            ->assertSee('Extra location')
            ->assertSee('name="quantity"', false)
            ->assertSee(route('admin.subscriptions.addons.store', $subscription), false)
            ->assertSee(route('admin.subscriptions.pause', $subscription), false);
    });

    it('escapes tenant names on the details page', function () {
        actingAsPlatformAdmin();

        $tenant = Tenant::factory()->create([
            'name' => '<script>alert("xss")</script>',
        ]);
        $subscription = Subscription::factory()->create(['tenant_id' => $tenant->id]);

        $this->get(route('admin.subscriptions.show', $subscription))
            ->assertOk()
            ->assertSee('&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert("xss")</script>', false);
    });
});

describe('lifecycle', function () {
    it('forbids lifecycle changes without subscriptions.manage', function () {
        actingAsPlatformAdmin([AdminPermission::SubscriptionsView]);

        $subscription = Subscription::factory()->trialing()->create();

        $this->patch(route('admin.subscriptions.activate', $subscription))->assertForbidden();
    });

    it('starts a trial on an active subscription', function () {
        actingAsPlatformAdmin();

        $plan = Plan::factory()->create(['trial_days' => 14]);
        $subscription = Subscription::factory()->create(['plan_id' => $plan->id]);

        $this->from(route('admin.subscriptions.show', $subscription))
            ->patch(route('admin.subscriptions.start-trial', $subscription))
            ->assertRedirect(route('admin.subscriptions.show', $subscription));

        $this->assertDatabaseHas('subscriptions', [
            'id' => $subscription->id,
            'status' => SubscriptionStatus::Trialing->value,
        ]);
        $this->assertDatabaseHas('subscription_events', [
            'subscription_id' => $subscription->id,
            'event_type' => SubscriptionEventType::TrialStarted->value,
            'old_status' => SubscriptionStatus::Active->value,
        ]);
    });

    it('activates a trialing subscription', function () {
        actingAsPlatformAdmin();

        $subscription = Subscription::factory()->trialing()->create();

        $this->from(route('admin.subscriptions.show', $subscription))
            ->patch(route('admin.subscriptions.activate', $subscription))
            ->assertRedirect(route('admin.subscriptions.show', $subscription));

        $this->assertDatabaseHas('subscriptions', [
            'id' => $subscription->id,
            'status' => SubscriptionStatus::Active->value,
        ]);
        $this->assertDatabaseHas('subscription_events', [
            'subscription_id' => $subscription->id,
            'event_type' => SubscriptionEventType::Activated->value,
            'old_status' => SubscriptionStatus::Trialing->value,
        ]);
    });

    it('pauses and resumes an active subscription', function () {
        actingAsPlatformAdmin();

        $subscription = Subscription::factory()->create();

        $this->patch(route('admin.subscriptions.pause', $subscription))
            ->assertRedirect(route('admin.subscriptions.show', $subscription));

        $this->assertDatabaseHas('subscriptions', [
            'id' => $subscription->id,
            'status' => SubscriptionStatus::Paused->value,
        ]);

        $this->patch(route('admin.subscriptions.resume', $subscription))
            ->assertRedirect(route('admin.subscriptions.show', $subscription));

        $this->assertDatabaseHas('subscriptions', [
            'id' => $subscription->id,
            'status' => SubscriptionStatus::Active->value,
        ]);
        $this->assertDatabaseHas('subscription_events', [
            'subscription_id' => $subscription->id,
            'event_type' => SubscriptionEventType::Paused->value,
        ]);
        $this->assertDatabaseHas('subscription_events', [
            'subscription_id' => $subscription->id,
            'event_type' => SubscriptionEventType::Resumed->value,
        ]);
    });

    it('cancels immediately when cancel at period end is unchecked', function () {
        actingAsPlatformAdmin();

        $subscription = Subscription::factory()->create();

        $this->patch(route('admin.subscriptions.cancel', $subscription), [
            'cancel_at_period_end' => '0',
        ])->assertRedirect(route('admin.subscriptions.show', $subscription));

        $this->assertDatabaseHas('subscriptions', [
            'id' => $subscription->id,
            'status' => SubscriptionStatus::Cancelled->value,
        ]);
        $this->assertDatabaseHas('subscription_events', [
            'subscription_id' => $subscription->id,
            'event_type' => SubscriptionEventType::Cancelled->value,
        ]);
    });

    it('schedules cancellation at period end', function () {
        actingAsPlatformAdmin();

        $subscription = Subscription::factory()->create();

        $this->patch(route('admin.subscriptions.cancel', $subscription), [
            'cancel_at_period_end' => '1',
        ])->assertRedirect(route('admin.subscriptions.show', $subscription));

        $this->assertDatabaseHas('subscriptions', [
            'id' => $subscription->id,
            'status' => SubscriptionStatus::Active->value,
            'cancel_at_period_end' => 1,
        ]);
        $this->assertDatabaseHas('subscription_events', [
            'subscription_id' => $subscription->id,
            'event_type' => SubscriptionEventType::CancellationScheduled->value,
        ]);
    });

    it('expires a current subscription', function () {
        actingAsPlatformAdmin();

        $subscription = Subscription::factory()->create();

        $this->patch(route('admin.subscriptions.expire', $subscription))
            ->assertRedirect(route('admin.subscriptions.show', $subscription));

        $this->assertDatabaseHas('subscriptions', [
            'id' => $subscription->id,
            'status' => SubscriptionStatus::Expired->value,
        ]);
        $this->assertDatabaseHas('subscription_events', [
            'subscription_id' => $subscription->id,
            'event_type' => SubscriptionEventType::Expired->value,
        ]);
    });

    it('marks an active subscription past due', function () {
        actingAsPlatformAdmin();

        $subscription = Subscription::factory()->create();

        $this->patch(route('admin.subscriptions.past-due', $subscription))
            ->assertRedirect(route('admin.subscriptions.show', $subscription));

        $this->assertDatabaseHas('subscriptions', [
            'id' => $subscription->id,
            'status' => SubscriptionStatus::PastDue->value,
        ]);
        expect($subscription->refresh()->grace_ends_at)->not->toBeNull();
    });

    it('stores a custom grace window on create', function () {
        actingAsPlatformAdmin();

        $tenant = Tenant::factory()->create();
        $plan = Plan::factory()->create(['trial_days' => 0]);

        $this->post(route('admin.subscriptions.store'), subscriptionPayload([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'start_trial' => '0',
            'grace_days' => '3',
        ]))->assertRedirect();

        $this->assertDatabaseHas('subscriptions', [
            'tenant_id' => $tenant->id,
            'grace_days' => 3,
        ]);
    });

    it('updates the grace window for a current subscription', function () {
        actingAsPlatformAdmin();

        $subscription = Subscription::factory()->create(['grace_days' => 7]);

        $this->patch(route('admin.subscriptions.grace-days', $subscription), [
            'grace_days' => 14,
        ])->assertRedirect(route('admin.subscriptions.show', $subscription));

        $this->assertDatabaseHas('subscriptions', [
            'id' => $subscription->id,
            'grace_days' => 14,
        ]);
        $this->assertDatabaseHas('subscription_events', [
            'subscription_id' => $subscription->id,
            'event_type' => SubscriptionEventType::GraceDaysUpdated->value,
        ]);
    });

    it('adds and removes an add-on on a current subscription', function () {
        actingAsPlatformAdmin();

        $subscription = Subscription::factory()->create();
        $addon = Addon::factory()->create(['name' => 'Extra location', 'price' => '9.00']);

        $this->post(route('admin.subscriptions.addons.store', $subscription), [
            'addon_id' => $addon->id,
            'quantity' => 2,
        ])->assertRedirect(route('admin.subscriptions.show', $subscription));

        $this->assertDatabaseHas('subscription_items', [
            'subscription_id' => $subscription->id,
            'item_type' => SubscriptionItemType::Addon->value,
            'reference_id' => $addon->id,
            'quantity' => 2,
        ]);
        $this->assertDatabaseHas('subscription_events', [
            'subscription_id' => $subscription->id,
            'event_type' => SubscriptionEventType::AddonAdded->value,
        ]);

        $this->patch(route('admin.subscriptions.addons.update', [$subscription, $addon]), [
            'quantity' => 3,
        ])->assertRedirect(route('admin.subscriptions.show', $subscription));

        $this->assertDatabaseHas('subscription_items', [
            'subscription_id' => $subscription->id,
            'item_type' => SubscriptionItemType::Addon->value,
            'reference_id' => $addon->id,
            'quantity' => 3,
        ]);
        $this->assertDatabaseHas('subscription_events', [
            'subscription_id' => $subscription->id,
            'event_type' => SubscriptionEventType::AddonQuantityUpdated->value,
        ]);

        $this->delete(route('admin.subscriptions.addons.destroy', [$subscription, $addon]))
            ->assertRedirect(route('admin.subscriptions.show', $subscription));

        $this->assertDatabaseMissing('subscription_items', [
            'subscription_id' => $subscription->id,
            'item_type' => SubscriptionItemType::Addon->value,
            'reference_id' => $addon->id,
        ]);
        $this->assertDatabaseHas('subscription_events', [
            'subscription_id' => $subscription->id,
            'event_type' => SubscriptionEventType::AddonRemoved->value,
        ]);
    });

    it('forbids add-on changes without subscriptions.manage', function () {
        actingAsPlatformAdmin([AdminPermission::SubscriptionsView]);

        $subscription = Subscription::factory()->create();
        $addon = Addon::factory()->create();

        $this->post(route('admin.subscriptions.addons.store', $subscription), [
            'addon_id' => $addon->id,
        ])->assertForbidden();
    });

    it('upgrades to a higher priced plan and records plan history', function () {
        actingAsPlatformAdmin();

        $starter = Plan::factory()->create(['name' => 'Starter', 'price' => '19.00']);
        $pro = Plan::factory()->create(['name' => 'Pro', 'price' => '49.00']);
        $subscription = Subscription::factory()->create(['plan_id' => $starter->id]);
        $subscription->items()->create([
            'item_type' => SubscriptionItemType::Plan,
            'reference_id' => $starter->id,
            'quantity' => 1,
            'unit_price' => '19.00',
        ]);

        $this->post(route('admin.subscriptions.change-plan', $subscription), [
            'plan_id' => $pro->id,
        ])->assertRedirect(route('admin.subscriptions.show', $subscription));

        $this->assertDatabaseHas('subscriptions', [
            'id' => $subscription->id,
            'plan_id' => $pro->id,
        ]);
        $this->assertDatabaseHas('subscription_items', [
            'subscription_id' => $subscription->id,
            'item_type' => SubscriptionItemType::Plan->value,
            'reference_id' => $pro->id,
            'unit_price' => '49.00',
        ]);
        $this->assertDatabaseHas('subscription_events', [
            'subscription_id' => $subscription->id,
            'event_type' => SubscriptionEventType::PlanUpgraded->value,
        ]);
    });

    it('downgrades to a lower priced plan', function () {
        actingAsPlatformAdmin();

        $pro = Plan::factory()->create(['name' => 'Pro', 'price' => '49.00']);
        $starter = Plan::factory()->create(['name' => 'Starter', 'price' => '19.00']);
        $subscription = Subscription::factory()->create(['plan_id' => $pro->id]);

        $this->post(route('admin.subscriptions.change-plan', $subscription), [
            'plan_id' => $starter->id,
        ])->assertRedirect(route('admin.subscriptions.show', $subscription));

        $this->assertDatabaseHas('subscriptions', [
            'id' => $subscription->id,
            'plan_id' => $starter->id,
        ]);
        $this->assertDatabaseHas('subscription_events', [
            'subscription_id' => $subscription->id,
            'event_type' => SubscriptionEventType::PlanDowngraded->value,
        ]);
    });

    it('does not activate a cancelled subscription', function () {
        actingAsPlatformAdmin();

        $subscription = Subscription::factory()->cancelled()->create();

        $this->from(route('admin.subscriptions.show', $subscription))
            ->patch(route('admin.subscriptions.activate', $subscription))
            ->assertRedirect();

        $this->assertDatabaseHas('subscriptions', [
            'id' => $subscription->id,
            'status' => SubscriptionStatus::Cancelled->value,
        ]);
        $this->assertDatabaseMissing('subscription_events', [
            'subscription_id' => $subscription->id,
            'event_type' => SubscriptionEventType::Activated->value,
        ]);
    });
});
