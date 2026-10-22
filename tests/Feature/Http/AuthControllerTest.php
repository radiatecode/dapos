<?php

use App\Enums\Permission;
use App\Models\User;
use DA\Admin\Enums\Features;
use DA\Admin\Models\Subscription;
use DA\Admin\Models\Tenant;
use Laravel\Sanctum\Sanctum;

describe('login', function () {
    it('returns a token for a tenant user', function () {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->forTenant($tenant)->withPermissions()->create([
            'email' => 'owner@shop.test',
            'password' => 'password',
        ]);

        $response = $this->postJson('/api/v1/login', [
            'email' => 'owner@shop.test',
            'password' => 'password',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonPath('data.user.email', 'owner@shop.test')
            ->assertJsonPath('data.user.currency', $tenant->currency)
            ->assertJsonPath('data.user.tenant.subscription', null);

        expect($response->json('data.token'))->toBeString()->not->toBeEmpty();
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'tokenable_type' => User::class,
        ]);
    });

    it('returns 422 when the credentials are invalid', function () {
        $tenant = Tenant::factory()->create();
        User::factory()->forTenant($tenant)->create([
            'email' => 'owner@shop.test',
            'password' => 'password',
        ]);

        $this->postJson('/api/v1/login', [
            'email' => 'owner@shop.test',
            'password' => 'wrong-password',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    });

    it('returns the subscription feature map', function () {
        $tenant = Tenant::factory()->create();
        $subscription = Subscription::factory()->create(['tenant_id' => $tenant->id]);
        $subscription->subscriptionFeatures()->create([
            'feature' => Features::POS->value,
            'value' => '1',
            'is_unlimited' => false,
        ]);
        $subscription->subscriptionFeatures()->create([
            'feature' => Features::CUSTOMERS->value,
            'value' => '0',
            'is_unlimited' => false,
        ]);
        $subscription->subscriptionFeatures()->create([
            'feature' => Features::PRODUCTS->value,
            'value' => null,
            'is_unlimited' => true,
        ]);
        User::factory()->forTenant($tenant)->create([
            'email' => 'owner@shop.test',
            'password' => 'password',
        ]);

        $this->postJson('/api/v1/login', [
            'email' => 'owner@shop.test',
            'password' => 'password',
        ])
            ->assertOk()
            ->assertJsonPath('data.user.tenant.subscription.id', $subscription->id)
            ->assertJsonPath('data.user.tenant.subscription.features.pos.enabled', true)
            ->assertJsonPath('data.user.tenant.subscription.features.pos.type', 'boolean')
            ->assertJsonPath('data.user.tenant.subscription.features.customers.enabled', false)
            ->assertJsonPath('data.user.tenant.subscription.features.products.enabled', true)
            ->assertJsonPath('data.user.tenant.subscription.features.products.unlimited', true)
            ->assertJsonMissingPath('data.user.tenant.subscription.features.sales');
    });

    it('returns 422 when a platform admin tries to sign in', function () {
        User::factory()->platformAdmin()->create([
            'email' => 'admin@radiate.test',
            'password' => 'password',
        ]);

        $this->postJson('/api/v1/login', [
            'email' => 'admin@radiate.test',
            'password' => 'password',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    });

    it('returns 403 when the super admin account is inactive', function () {
        $tenant = Tenant::factory()->create();
        User::factory()->forTenant($tenant)->create([
            'email' => 'owner@shop.test',
            'password' => 'password',
            'is_super_admin' => true,
            'is_active' => false,
        ]);

        $this->postJson('/api/v1/login', [
            'email' => 'owner@shop.test',
            'password' => 'password',
        ])->assertForbidden();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    });

    it('returns 403 when the tenant is inactive', function () {
        $tenant = Tenant::factory()->inactive()->create();
        User::factory()->forTenant($tenant)->create([
            'email' => 'owner@shop.test',
            'password' => 'password',
            'is_active' => true,
        ]);

        $this->postJson('/api/v1/login', [
            'email' => 'owner@shop.test',
            'password' => 'password',
        ])->assertForbidden();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    });

    it('returns 403 when the tenant is suspended', function () {
        $tenant = Tenant::factory()->suspended()->create();
        User::factory()->forTenant($tenant)->create([
            'email' => 'owner@shop.test',
            'password' => 'password',
        ]);

        $this->postJson('/api/v1/login', [
            'email' => 'owner@shop.test',
            'password' => 'password',
        ])->assertForbidden();
    });

    it('returns 422 when the email is missing', function () {
        $this->postJson('/api/v1/login', [
            'password' => 'password',
        ])
            ->assertUnprocessable()
            ->assertJsonPath('errors.email.0', 'The email field is required.');
    });
});

describe('logout and me', function () {
    it('returns 401 when no token is provided', function (string $method, string $uri) {
        $this->json($method, $uri)->assertUnauthorized();
    })->with([
        ['POST', '/api/v1/logout'],
        ['GET', '/api/v1/me'],
    ]);

    it('returns the authenticated tenant user', function () {
        $user = actingAsTenantUser();

        $this->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.email', $user->email)
            ->assertJsonPath('data.role.permissions.0', Permission::DashboardView->value)
            ->assertJsonPath('data.currency', $user->tenant->currency)
            ->assertJsonPath('data.tenant.subscription.features.pos.enabled', true);
    });

    it('returns 403 for a platform admin', function () {
        Sanctum::actingAs(User::factory()->platformAdmin()->create());

        $this->getJson('/api/v1/me')->assertForbidden();
    });

    it('revokes the current token', function () {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->forTenant($tenant)->withPermissions()->create([
            'email' => 'owner@shop.test',
            'password' => 'password',
        ]);
        $token = $user->createToken('pos')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/v1/logout')
            ->assertNoContent();

        expect($user->tokens()->count())->toBe(0);

        $this->app['auth']->forgetGuards();
        $this->flushHeaders();

        $this->withToken($token)
            ->getJson('/api/v1/me')
            ->assertUnauthorized();
    });
});
