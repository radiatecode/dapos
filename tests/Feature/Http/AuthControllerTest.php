<?php

use App\Enums\Permission;
use App\Models\User;
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
            ->assertJsonPath('data.user.email', 'owner@shop.test');

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
            ->assertJsonPath('data.permissions.0', Permission::DashboardView->value);
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
