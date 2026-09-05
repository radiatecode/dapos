<?php

use DA\Admin\Enums\AdminPermission;
use DA\Admin\Enums\SubscriptionEventType;
use DA\Admin\Models\Subscription;
use DA\Admin\Models\SubscriptionEvent;
use DA\Admin\Models\Tenant;

describe('index', function () {
    it('redirects guests from the events list to login', function () {
        $this->get(route('admin.subscription-events.index'))
            ->assertRedirect(route('admin.login'));
    });

    it('forbids an admin without subscription-events.view', function () {
        actingAsPlatformAdmin([AdminPermission::SubscriptionsView]);

        $this->get(route('admin.subscription-events.index'))->assertForbidden();
    });

    it('renders the events datatable for a platform admin', function () {
        actingAsPlatformAdmin();

        $tenant = Tenant::factory()->create(['name' => 'Harbor Cafe']);
        $subscription = Subscription::factory()->create(['tenant_id' => $tenant->id]);
        SubscriptionEvent::factory()->create([
            'tenant_id' => $tenant->id,
            'subscription_id' => $subscription->id,
            'event_type' => SubscriptionEventType::Created,
        ]);

        $this->get(route('admin.subscription-events.index'))
            ->assertOk()
            ->assertSee('subscription-events-table', false)
            ->assertSee('Subscription events');
    });
});
