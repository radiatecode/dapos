<?php

use App\Enums\Permission;
use DA\Admin\Models\Tenant;
use DA\Inventory\Models\Brand;

describe('authentication and authorization', function () {
    it('returns 401 when no token is provided', function (string $method, string $uri) {
        $this->json($method, $uri, brandPayload())->assertUnauthorized();
    })->with([
        ['GET', '/api/v1/brands'],
        ['POST', '/api/v1/brands'],
        ['GET', '/api/v1/brands/1'],
        ['POST', '/api/v1/brands/1'],
        ['POST', '/api/v1/brands/delete'],
    ]);

    it('returns 403 when the user lacks the required permission', function () {
        actingAsTenantUser(permissions: [Permission::CategoriesView]);

        $this->getJson('/api/v1/brands')->assertForbidden();
    });
});

describe('index and show', function () {
    it('lists brands for the current tenant newest first', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::BrandsView]);
        $older = Brand::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Older']);
        $newer = Brand::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Newer']);
        Brand::factory()->create(['name' => 'Other tenant']);

        $this->getJson('/api/v1/brands')
            ->assertOk()
            ->assertJsonPath('data.0.id', $newer->id)
            ->assertJsonPath('data.1.id', $older->id)
            ->assertJsonMissing(['name' => 'Other tenant']);
    });

    it('filters brands by name', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::BrandsView]);
        Brand::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Acme Foods']);
        Brand::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Other Brand']);

        $this->getJson('/api/v1/brands?name=Acme')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Acme Foods');
    });

    it('returns 404 when tenant A requests a tenant B brand', function () {
        $tenantA = Tenant::factory()->create();
        actingAsTenantUser($tenantA, [Permission::BrandsView]);
        $foreign = Brand::factory()->create();

        $this->getJson('/api/v1/brands/'.$foreign->id)->assertNotFound();
    });
});

describe('store', function () {
    it('creates a brand and generates a slug from the name', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::BrandsCreate]);

        $this->postJson('/api/v1/brands', [
            'name' => 'House Brand',
        ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'House Brand')
            ->assertJsonPath('data.slug', 'house-brand')
            ->assertJsonPath('data.is_active', true);

        $this->assertDatabaseHas('brands', [
            'name' => 'House Brand',
            'slug' => 'house-brand',
            'tenant_id' => $tenant->id,
        ]);
    });

    it('returns 422 when the name is missing', function () {
        actingAsTenantUser(permissions: [Permission::BrandsCreate]);

        $this->postJson('/api/v1/brands', brandPayload(['name' => '']))
            ->assertUnprocessable()
            ->assertJsonPath('errors.name.0', 'The name field is required.');
    });

    it('returns 422 when the slug is already taken', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::BrandsCreate]);
        Brand::factory()->create([
            'tenant_id' => $tenant->id,
            'slug' => 'acme',
        ]);

        $this->postJson('/api/v1/brands', brandPayload())
            ->assertUnprocessable()
            ->assertJsonPath('errors.slug.0', 'The slug has already been taken.');
    });
});

describe('update and destroy', function () {
    it('updates the brand', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::BrandsUpdate]);
        $brand = Brand::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Old',
            'slug' => 'old',
        ]);

        $this->postJson('/api/v1/brands/'.$brand->id, [
            'name' => 'Updated',
            'slug' => 'updated',
        ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Updated')
            ->assertJsonPath('data.slug', 'updated');

        $this->assertDatabaseHas('brands', [
            'id' => $brand->id,
            'name' => 'Updated',
            'slug' => 'updated',
        ]);
    });

    it('deletes the brand', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::BrandsDelete]);
        $brand = Brand::factory()->create(['tenant_id' => $tenant->id]);

        $this->postJson('/api/v1/brands/delete', [
            'action' => 'delete',
            'ids' => [$brand->id],
        ])->assertOk();

        $this->assertDatabaseMissing('brands', ['id' => $brand->id]);
    });

    it('rejects a delete request with an invalid action', function () {
        actingAsTenantUser(permissions: [Permission::BrandsDelete]);

        $this->postJson('/api/v1/brands/delete', [
            'action' => 'archive',
            'ids' => [1],
        ])->assertStatus(400);
    });
});
