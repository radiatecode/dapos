<?php

use DA\Admin\Exceptions\TenantContextMissingException;
use DA\Admin\Models\Tenant;
use DA\Admin\Models\User;
use DA\Admin\Services\Tenancy\TenantContext;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;
use Tests\Fixtures\TenantOwnedItem;

beforeEach(function () {
    Route::middleware(['auth:sanctum', 'tenant', SubstituteBindings::class])
        ->get('/api/tenant-owned-items/{item}', function (TenantOwnedItem $item) {
            return response()->json($item);
        });
});

it('excludes other tenants rows when context is set', function () {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();
    $context = app(TenantContext::class);

    $context->set($tenantA);
    TenantOwnedItem::query()->create(['title' => 'A item']);

    $context->set($tenantB);
    TenantOwnedItem::query()->create(['title' => 'B item']);

    $context->set($tenantA);

    expect(TenantOwnedItem::query()->pluck('title')->all())->toBe(['A item']);
});

it('throws when a tenant-owned model is queried without context', function () {
    TenantOwnedItem::query()->get();
})->throws(TenantContextMissingException::class);

it('stamps tenant_id from context and ignores a submitted tenant_id', function () {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();

    app(TenantContext::class)->set($tenantA);

    $item = TenantOwnedItem::query()->create([
        'title' => 'Owned by A',
        'tenant_id' => $tenantB->id,
    ]);

    expect($item->tenant_id)->toBe($tenantA->id);
    expect($item->fresh()->tenant_id)->toBe($tenantA->id);
});

it('returns 404 when tenant A requests tenant B data', function () {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();
    $userA = User::factory()->forTenant($tenantA)->create();
    $context = app(TenantContext::class);

    $context->set($tenantB);
    $itemB = TenantOwnedItem::query()->create(['title' => 'Secret']);
    $context->forget();

    Sanctum::actingAs($userA);

    $this->getJson('/api/tenant-owned-items/'.$itemB->id)
        ->assertNotFound();
});

it('returns the record when the owner requests it', function () {
    $tenantA = Tenant::factory()->create();
    $userA = User::factory()->forTenant($tenantA)->create();
    $context = app(TenantContext::class);

    $context->set($tenantA);
    $itemA = TenantOwnedItem::query()->create(['title' => 'Visible']);
    $context->forget();

    Sanctum::actingAs($userA);

    $this->getJson('/api/tenant-owned-items/'.$itemA->id)
        ->assertOk()
        ->assertJsonPath('title', 'Visible');
});
