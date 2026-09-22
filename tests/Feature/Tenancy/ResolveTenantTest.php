<?php

use App\Enums\UserRole;
use App\Models\User;
use DA\Admin\Models\Tenant;
use DA\Admin\Services\Tenancy\TenantContext;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    Route::middleware(['auth:sanctum', 'tenant'])
        ->get('/api/tenant-context-check', function (TenantContext $context) {
            return [
                'has' => $context->has(),
                'id' => $context->id(),
            ];
        });
});

it('returns 401 when no token is provided', function () {
    $this->getJson('/api/tenant-context-check')->assertUnauthorized();
});

it('does not populate tenant context for a platform admin', function () {
    Sanctum::actingAs(User::factory()->platformAdmin()->create());

    $this->getJson('/api/tenant-context-check')
        ->assertOk()
        ->assertJsonPath('has', false)
        ->assertJsonPath('id', null);
});

it('resolves tenant context from the authenticated tenant user', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->forTenant($tenant)->create();

    Sanctum::actingAs($user);

    $this->getJson('/api/tenant-context-check')
        ->assertOk()
        ->assertJsonPath('has', true)
        ->assertJsonPath('id', $tenant->id);
});

it('returns 403 for a tenant user whose tenant is suspended', function () {
    $tenant = Tenant::factory()->suspended()->create();
    $user = User::factory()->forTenant($tenant)->create();

    Sanctum::actingAs($user);

    $this->getJson('/api/tenant-context-check')->assertForbidden();
});

it('returns 403 for a tenant user without a tenant', function () {
    $user = User::factory()->create([
        'role' => UserRole::TenantUser,
        'tenant_id' => null,
    ]);

    Sanctum::actingAs($user);

    $this->getJson('/api/tenant-context-check')->assertForbidden();
});
