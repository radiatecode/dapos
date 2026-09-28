<?php

use App\Enums\Permission;
use DA\Admin\Models\Tenant;
use DA\Inventory\Models\Purchase;
use DA\Inventory\Models\Store;

describe('authentication and authorization', function () {
    it('returns 401 when no token is provided', function (string $method, string $uri) {
        $this->json($method, $uri, storePayload())->assertUnauthorized();
    })->with([
        ['GET', '/api/v1/stores'],
        ['POST', '/api/v1/stores'],
        ['GET', '/api/v1/stores/1'],
        ['PUT', '/api/v1/stores/1'],
        ['POST', '/api/v1/stores/delete'],
    ]);

    it('returns 403 when the user lacks the required permission', function () {
        actingAsTenantUser(permissions: [Permission::CategoriesView]);

        $this->getJson('/api/v1/stores')->assertForbidden();
    });
});

describe('index and show', function () {
    it('lists stores for the current tenant newest first', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::StoresView]);
        $older = Store::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Older']);
        $newer = Store::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Newer']);
        $foreignTenant = Tenant::factory()->create();
        Store::withoutEvents(fn () => Store::factory()->create([
            'tenant_id' => $foreignTenant->id,
            'name' => 'Other tenant',
        ]));

        $this->getJson('/api/v1/stores')
            ->assertOk()
            ->assertJsonPath('data.0.id', $newer->id)
            ->assertJsonPath('data.1.id', $older->id)
            ->assertJsonMissing(['name' => 'Other tenant']);
    });

    it('filters stores by name', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::StoresView]);
        Store::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Main Warehouse']);
        Store::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Outlet Counter']);

        $this->getJson('/api/v1/stores?name=Warehouse&limit=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Main Warehouse')
            ->assertJsonPath('meta.per_page', 1);
    });

    it('returns 404 when tenant A requests a tenant B store', function () {
        $tenantA = Tenant::factory()->create();
        actingAsTenantUser($tenantA, [Permission::StoresView]);
        $foreign = Store::withoutEvents(fn () => Store::factory()->create());

        $this->getJson('/api/v1/stores/'.$foreign->id)->assertNotFound();
    });
});

describe('store', function () {
    it('creates a store for the authenticated tenant', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::StoresCreate]);

        $this->postJson('/api/v1/stores', storePayload())
            ->assertCreated()
            ->assertJsonPath('data.name', 'Main Store')
            ->assertJsonPath('data.address', '12 Market Road, Dhaka')
            ->assertJsonPath('data.is_active', true)
            ->assertJsonPath('data.status', 'Active');

        $this->assertDatabaseHas('stores', [
            'name' => 'Main Store',
            'tenant_id' => $tenant->id,
            'is_active' => true,
        ]);
    });

    it('stores an inactive store', function () {
        actingAsTenantUser(permissions: [Permission::StoresCreate]);

        $this->postJson('/api/v1/stores', storePayload([
            'is_active' => false,
        ]))
            ->assertCreated()
            ->assertJsonPath('data.is_active', false)
            ->assertJsonPath('data.status', 'Inactive');
    });

    it('returns 422 when required fields are missing', function () {
        actingAsTenantUser(permissions: [Permission::StoresCreate]);

        $this->postJson('/api/v1/stores', [])
            ->assertUnprocessable()
            ->assertJsonPath('errors.name.0', 'The name field is required.')
            ->assertJsonValidationErrors(['name', 'address']);
    });
});

describe('update and destroy', function () {
    it('updates the store', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::StoresUpdate]);
        $store = Store::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Old',
        ]);

        $this->putJson('/api/v1/stores/'.$store->id, storePayload([
            'name' => 'Branch Store',
            'address' => '88 Lake Road',
        ]))
            ->assertOk()
            ->assertJsonPath('data.name', 'Branch Store')
            ->assertJsonPath('data.address', '88 Lake Road');

        $this->assertDatabaseHas('stores', [
            'id' => $store->id,
            'name' => 'Branch Store',
            'address' => '88 Lake Road',
            'tenant_id' => $tenant->id,
        ]);
    });

    it('returns 404 when updating another tenants store', function () {
        $tenantA = Tenant::factory()->create();
        actingAsTenantUser($tenantA, [Permission::StoresUpdate]);
        $foreign = Store::withoutEvents(fn () => Store::factory()->create());

        $this->putJson('/api/v1/stores/'.$foreign->id, storePayload())
            ->assertNotFound();
    });

    it('deletes the store', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::StoresDelete]);
        $store = Store::factory()->create(['tenant_id' => $tenant->id]);

        $this->postJson('/api/v1/stores/delete', [
            'action' => 'delete',
            'ids' => [$store->id],
        ])->assertOk()
            ->assertJsonPath('message', 'Store deleted successfully');

        $this->assertDatabaseMissing('stores', ['id' => $store->id]);
    });

    it('rejects deleting a store that is used on a purchase', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::StoresDelete]);
        $store = Store::factory()->create(['tenant_id' => $tenant->id]);
        Purchase::factory()->create([
            'tenant_id' => $tenant->id,
            'store_id' => $store->id,
        ]);

        $this->postJson('/api/v1/stores/delete', [
            'action' => 'delete',
            'ids' => [$store->id],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors([
                'ids' => 'This store cannot be deleted because it is used by stock or a purchase.',
            ]);

        $this->assertDatabaseHas('stores', ['id' => $store->id]);
    });

    it('rejects a delete request with an invalid action', function () {
        actingAsTenantUser(permissions: [Permission::StoresDelete]);

        $this->postJson('/api/v1/stores/delete', [
            'action' => 'archive',
            'ids' => [1],
        ])->assertStatus(400);
    });
});
