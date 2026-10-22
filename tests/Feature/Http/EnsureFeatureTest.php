<?php

use App\Enums\Permission;
use DA\Admin\Enums\Features;
use DA\Admin\Models\Subscription;
use DA\Admin\Models\Tenant;

it('returns 401 when no token is provided', function () {
    $this->getJson('/api/v1/customers')->assertUnauthorized();
});

it('returns 403 when the subscription does not include the feature', function () {
    $tenant = Tenant::factory()->create();
    $subscription = Subscription::factory()->create(['tenant_id' => $tenant->id]);
    $subscription->subscriptionFeatures()->create([
        'feature' => Features::POS->value,
        'value' => '1',
        'is_unlimited' => false,
    ]);

    actingAsTenantUser($tenant, [Permission::CustomersView]);

    $this->getJson('/api/v1/customers')
        ->assertForbidden()
        ->assertJsonPath('message', 'This feature is not available on the current subscription.');
});

it('returns 403 when a boolean feature is turned off', function () {
    $tenant = Tenant::factory()->create();
    $user = actingAsTenantUser($tenant, [Permission::CustomersView]);
    $user->tenant->subscription->subscriptionFeatures()
        ->where('feature', Features::CUSTOMERS->value)
        ->update([
            'value' => '0',
            'is_unlimited' => false,
        ]);
    $user->unsetRelation('tenant');

    $this->getJson('/api/v1/customers')
        ->assertForbidden()
        ->assertJsonPath('message', 'This feature is not available on the current subscription.');
});

it('returns customers when the subscription includes the feature', function () {
    actingAsTenantUser(permissions: [Permission::CustomersView]);

    $this->getJson('/api/v1/customers')->assertOk();
});
