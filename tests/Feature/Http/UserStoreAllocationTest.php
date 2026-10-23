<?php

use App\Enums\Permission;
use App\Models\User;
use DA\Admin\Models\Tenant;
use DA\Inventory\Models\Product;
use DA\Inventory\Models\ProductVariant;
use DA\Inventory\Models\Sale;
use DA\Inventory\Models\StockTransfer;
use DA\Inventory\Models\Store;
use DA\Inventory\Models\Supplier;
use Laravel\Sanctum\Sanctum;

describe('authentication and authorization', function () {
    it('returns 401 when no token is provided', function () {
        $this->putJson('/api/v1/users/1/stores', [
            'store_ids' => [],
        ])->assertUnauthorized();
    });

    it('returns 403 when the user lacks permission to update users', function () {
        actingAsTenantUser(permissions: [Permission::UsersView]);

        $this->putJson('/api/v1/users/1/stores', [
            'store_ids' => [],
        ])->assertForbidden();
    });
});

describe('assignment', function () {
    it('allocates multiple stores and returns them on the user', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::UsersUpdate, Permission::UsersView]);
        $main = Store::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Main']);
        $branch = Store::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Branch']);
        $cashier = User::factory()->forTenant($tenant)->create();

        $response = $this->putJson('/api/v1/users/'.$cashier->id.'/stores', [
            'store_ids' => [$branch->id, $main->id],
        ]);

        $response->assertOk()
            ->assertJsonPath('data.manages_all_stores', false);

        expect(collect($response->json('data.stores'))->pluck('id')->all())
            ->toEqualCanonicalizing([$main->id, $branch->id]);

        $this->assertDatabaseHas('store_user', [
            'tenant_id' => $tenant->id,
            'user_id' => $cashier->id,
            'store_id' => $main->id,
        ]);
        $this->assertDatabaseHas('store_user', [
            'user_id' => $cashier->id,
            'store_id' => $branch->id,
        ]);

        $shown = $this->getJson('/api/v1/users/'.$cashier->id)->assertOk();

        expect(collect($shown->json('data.stores'))->pluck('name')->all())
            ->toEqualCanonicalizing(['Main', 'Branch']);
    });

    it('clears the allocation so the user manages every store', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::UsersUpdate]);
        $store = Store::factory()->create(['tenant_id' => $tenant->id]);
        $cashier = User::factory()->forTenant($tenant)->withPermissions([
            Permission::StoresView,
        ])->create();

        $this->putJson('/api/v1/users/'.$cashier->id.'/stores', [
            'store_ids' => [$store->id],
        ])->assertOk();

        $this->putJson('/api/v1/users/'.$cashier->id.'/stores', [
            'store_ids' => [],
        ])->assertOk()
            ->assertJsonPath('data.manages_all_stores', true)
            ->assertJsonPath('data.stores', []);

        $this->assertDatabaseMissing('store_user', [
            'user_id' => $cashier->id,
        ]);
    });

    it('returns 422 when a store belongs to another tenant', function () {
        $foreignStore = Store::factory()->create([
            'tenant_id' => Tenant::factory()->create()->id,
        ]);
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::UsersUpdate]);
        $cashier = User::factory()->forTenant($tenant)->create();

        $this->putJson('/api/v1/users/'.$cashier->id.'/stores', [
            'store_ids' => [$foreignStore->id],
        ])->assertUnprocessable()
            ->assertJsonPath('errors.store_ids.0', 'The selected store is invalid.');
    });

    it('returns 422 when store ids are missing', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::UsersUpdate]);
        $cashier = User::factory()->forTenant($tenant)->create();

        $this->putJson('/api/v1/users/'.$cashier->id.'/stores', [])
            ->assertUnprocessable()
            ->assertJsonPath('errors.store_ids.0', 'The stores field is required.');
    });

    it('returns 422 when allocating stores to the business owner', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::UsersUpdate]);
        $store = Store::factory()->create(['tenant_id' => $tenant->id]);
        $owner = User::factory()->forTenant($tenant)->create([
            'is_super_admin' => true,
        ]);

        $this->putJson('/api/v1/users/'.$owner->id.'/stores', [
            'store_ids' => [$store->id],
        ])->assertUnprocessable()
            ->assertJsonPath('errors.store_ids.0', 'The business owner always manages every store.');
    });

    it('creates a user limited to the selected stores', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::UsersCreate]);
        $store = Store::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Counter']);

        $response = $this->postJson('/api/v1/users', tenantUserPayload([
            'store_ids' => [$store->id],
        ]));

        $response->assertCreated()
            ->assertJsonPath('data.manages_all_stores', false)
            ->assertJsonPath('data.stores.0.id', $store->id)
            ->assertJsonPath('data.stores.0.name', 'Counter');
    });
});

describe('store access', function () {
    it('lets a user with no allocation manage every store', function () {
        $tenant = Tenant::factory()->create();
        $admin = actingAsTenantUser($tenant, [Permission::UsersUpdate, Permission::StoresView, Permission::SalesView]);
        $main = Store::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Main']);
        $branch = Store::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Branch']);
        Sale::factory()->create([
            'tenant_id' => $tenant->id,
            'store_id' => $main->id,
            'cashier_id' => $admin->id,
            'invoice_number' => 'INV-MAIN',
        ]);
        Sale::factory()->create([
            'tenant_id' => $tenant->id,
            'store_id' => $branch->id,
            'cashier_id' => $admin->id,
            'invoice_number' => 'INV-BRANCH',
        ]);
        $cashier = User::factory()->forTenant($tenant)->withPermissions([
            Permission::StoresView,
            Permission::SalesView,
        ])->create();

        Sanctum::actingAs($cashier);

        $this->getJson('/api/v1/stores')
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $this->getJson('/api/v1/sales')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    });

    it('limits stores, sales, and the store dropdown to the allocated stores', function () {
        $tenant = Tenant::factory()->create();
        $admin = actingAsTenantUser($tenant, [
            Permission::UsersUpdate,
            Permission::StoresView,
            Permission::StoresUpdate,
            Permission::SalesView,
        ]);
        $main = Store::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Main']);
        $branch = Store::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Branch']);
        $mainSale = Sale::factory()->create([
            'tenant_id' => $tenant->id,
            'store_id' => $main->id,
            'cashier_id' => $admin->id,
            'invoice_number' => 'INV-MAIN',
        ]);
        Sale::factory()->create([
            'tenant_id' => $tenant->id,
            'store_id' => $branch->id,
            'cashier_id' => $admin->id,
            'invoice_number' => 'INV-BRANCH',
        ]);
        $cashier = User::factory()->forTenant($tenant)->withPermissions([
            Permission::StoresView,
            Permission::StoresUpdate,
            Permission::SalesView,
        ])->create();

        $this->putJson('/api/v1/users/'.$cashier->id.'/stores', [
            'store_ids' => [$main->id],
        ])->assertOk();

        Sanctum::actingAs($cashier);

        $this->getJson('/api/v1/stores')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $main->id)
            ->assertJsonMissing(['name' => 'Branch']);

        $this->getJson('/api/v1/stores-dropdown')
            ->assertOk()
            ->assertJsonCount(1, 'results')
            ->assertJsonPath('results.0.id', $main->id);

        $this->getJson('/api/v1/stores/'.$branch->id)->assertNotFound();

        $this->putJson('/api/v1/stores/'.$branch->id, storePayload([
            'name' => 'Taken',
        ]))->assertNotFound();

        $this->getJson('/api/v1/sales')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $mainSale->id);
    });

    it('hides a stock transfer unless both stores are allocated', function () {
        $tenant = Tenant::factory()->create();
        $admin = actingAsTenantUser($tenant, [Permission::UsersUpdate]);
        $main = Store::factory()->create(['tenant_id' => $tenant->id]);
        $branch = Store::factory()->create(['tenant_id' => $tenant->id]);
        $variant = ProductVariant::factory()->create(['tenant_id' => $tenant->id]);
        $transfer = StockTransfer::factory()->create([
            'tenant_id' => $tenant->id,
            'product_variant_id' => $variant->id,
            'from_store_id' => $main->id,
            'to_store_id' => $branch->id,
            'created_by' => $admin->id,
        ]);
        $cashier = User::factory()->forTenant($tenant)->withPermissions([
            Permission::InventoryTransferView,
        ])->create();

        $this->putJson('/api/v1/users/'.$cashier->id.'/stores', [
            'store_ids' => [$main->id],
        ])->assertOk();

        Sanctum::actingAs($cashier);

        $this->getJson('/api/v1/stock-transfers')
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->getJson('/api/v1/stock-transfers/'.$transfer->id)->assertNotFound();
    });

    it('returns 422 when an allocated user purchases into another store', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::UsersUpdate, Permission::PurchasingCreate]);
        $allowed = Store::factory()->create(['tenant_id' => $tenant->id]);
        $blocked = Store::factory()->create(['tenant_id' => $tenant->id]);
        $supplier = Supplier::factory()->create(['tenant_id' => $tenant->id]);
        $product = Product::factory()->create(['tenant_id' => $tenant->id]);
        $variant = ProductVariant::factory()->create([
            'tenant_id' => $tenant->id,
            'product_id' => $product->id,
        ]);
        $cashier = User::factory()->forTenant($tenant)->withPermissions([
            Permission::PurchasingCreate,
        ])->create();

        $this->putJson('/api/v1/users/'.$cashier->id.'/stores', [
            'store_ids' => [$allowed->id],
        ])->assertOk();

        Sanctum::actingAs($cashier);

        $this->postJson('/api/v1/purchases', purchasePayload([
            'supplier_id' => $supplier->id,
            'store_id' => $blocked->id,
            'discount_amount' => '0',
            'tax_amount' => '0',
            'items' => [[
                'product_variant_id' => $variant->id,
                'quantity' => '2',
                'unit_cost' => '10',
                'discount_amount' => '0',
                'tax_amount' => '0',
            ]],
        ]))->assertUnprocessable()
            ->assertJsonPath('errors.store_id.0', 'The selected store is not allocated to you.');

        $this->assertDatabaseCount('purchases', 0);
    });

    it('adds a newly created store to a user who is already limited', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::UsersUpdate]);
        $existing = Store::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Existing']);
        $cashier = User::factory()->forTenant($tenant)->withPermissions([
            Permission::StoresView,
            Permission::StoresCreate,
        ])->create();

        $this->putJson('/api/v1/users/'.$cashier->id.'/stores', [
            'store_ids' => [$existing->id],
        ])->assertOk();

        Sanctum::actingAs($cashier);

        $created = $this->postJson('/api/v1/stores', storePayload([
            'name' => 'New counter',
        ]))->assertCreated();

        $stores = $this->getJson('/api/v1/stores')->assertOk();

        expect(collect($stores->json('data'))->pluck('id')->all())
            ->toEqualCanonicalizing([$existing->id, $created->json('data.id')]);
    });

    it('stops a limited user from granting access they do not have', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::UsersUpdate, Permission::UsersCreate]);
        $allowed = Store::factory()->create(['tenant_id' => $tenant->id]);
        $blocked = Store::factory()->create(['tenant_id' => $tenant->id]);
        $hidden = Store::factory()->create(['tenant_id' => $tenant->id]);
        $manager = User::factory()->forTenant($tenant)->withPermissions([
            Permission::UsersUpdate,
            Permission::UsersCreate,
        ])->create();
        $cashier = User::factory()->forTenant($tenant)->create();

        $this->putJson('/api/v1/users/'.$manager->id.'/stores', [
            'store_ids' => [$allowed->id],
        ])->assertOk();
        $this->putJson('/api/v1/users/'.$cashier->id.'/stores', [
            'store_ids' => [$allowed->id, $hidden->id],
        ])->assertOk();

        Sanctum::actingAs($manager);

        $this->putJson('/api/v1/users/'.$cashier->id.'/stores', [
            'store_ids' => [$blocked->id],
        ])->assertUnprocessable()
            ->assertJsonPath('errors.store_ids.0', 'You can only allocate stores assigned to you.');

        $this->putJson('/api/v1/users/'.$cashier->id.'/stores', [
            'store_ids' => [],
        ])->assertUnprocessable()
            ->assertJsonPath('errors.store_ids.0', 'Select at least one store. An administrator can remove the store limit.');

        $this->postJson('/api/v1/users', tenantUserPayload([
            'email' => 'open@shop.test',
        ]))->assertUnprocessable()
            ->assertJsonPath('errors.store_ids.0', 'Select at least one store. An administrator can remove the store limit.');

        $this->assertDatabaseMissing('users', [
            'email' => 'open@shop.test',
        ]);

        $this->putJson('/api/v1/users/'.$cashier->id.'/stores', [
            'store_ids' => [$allowed->id],
        ])->assertOk();

        $this->assertDatabaseHas('store_user', [
            'user_id' => $cashier->id,
            'store_id' => $hidden->id,
        ]);
        $this->assertDatabaseHas('store_user', [
            'user_id' => $cashier->id,
            'store_id' => $allowed->id,
        ]);
    });
});
