<?php

use App\Enums\Permission;
use DA\Admin\Models\Tenant;
use DA\Inventory\Models\Purchase;
use DA\Inventory\Models\Supplier;

describe('authentication and authorization', function () {
    it('returns 401 when no token is provided', function (string $method, string $uri) {
        $this->json($method, $uri, supplierPayload())->assertUnauthorized();
    })->with([
        ['GET', '/api/v1/suppliers'],
        ['POST', '/api/v1/suppliers'],
        ['GET', '/api/v1/suppliers/1'],
        ['PUT', '/api/v1/suppliers/1'],
        ['POST', '/api/v1/suppliers/delete'],
    ]);

    it('returns 403 when the user lacks the required permission', function () {
        actingAsTenantUser(permissions: [Permission::CategoriesView]);

        $this->getJson('/api/v1/suppliers')->assertForbidden();
    });
});

describe('index and show', function () {
    it('lists suppliers for the current tenant newest first', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::SuppliersView]);
        $older = Supplier::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Older']);
        $newer = Supplier::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Newer']);
        $foreignTenant = Tenant::factory()->create();
        Supplier::withoutEvents(fn () => Supplier::factory()->create([
            'tenant_id' => $foreignTenant->id,
            'name' => 'Other tenant',
        ]));

        $this->getJson('/api/v1/suppliers')
            ->assertOk()
            ->assertJsonPath('data.0.id', $newer->id)
            ->assertJsonPath('data.1.id', $older->id)
            ->assertJsonMissing(['name' => 'Other tenant']);
    });

    it('filters suppliers by name, email, and phone', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::SuppliersView]);
        Supplier::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Acme Foods',
            'email' => 'acme@example.com',
            'phone' => '01711111111',
        ]);
        Supplier::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Other Supply',
            'email' => 'other@example.com',
            'phone' => '01822222222',
        ]);

        $this->getJson('/api/v1/suppliers?name=Acme&limit=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Acme Foods')
            ->assertJsonPath('meta.per_page', 1);

        $this->getJson('/api/v1/suppliers?email=acme@')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.email', 'acme@example.com');

        $this->getJson('/api/v1/suppliers?phone=01822')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.phone', '01822222222');
    });

    it('returns 404 when tenant A requests a tenant B supplier', function () {
        $tenantA = Tenant::factory()->create();
        actingAsTenantUser($tenantA, [Permission::SuppliersView]);
        $foreign = Supplier::withoutEvents(fn () => Supplier::factory()->create());

        $this->getJson('/api/v1/suppliers/'.$foreign->id)->assertNotFound();
    });
});

describe('store', function () {
    it('creates a supplier for the authenticated tenant', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::SuppliersCreate]);

        $this->postJson('/api/v1/suppliers', supplierPayload([
            'city' => null,
            'state' => null,
            'country' => null,
            'postal_code' => null,
        ]))
            ->assertCreated()
            ->assertJsonPath('data.name', 'Acme Supplies')
            ->assertJsonPath('data.email', 'orders@acme.test')
            ->assertJsonPath('data.phone', '01700000000')
            ->assertJsonPath('data.address', '12 Market Road')
            ->assertJsonPath('data.city', null)
            ->assertJsonPath('data.is_active', true)
            ->assertJsonPath('data.status', 'Active');

        $this->assertDatabaseHas('suppliers', [
            'name' => 'Acme Supplies',
            'email' => 'orders@acme.test',
            'tenant_id' => $tenant->id,
            'city' => null,
        ]);
    });

    it('stores an inactive supplier', function () {
        actingAsTenantUser(permissions: [Permission::SuppliersCreate]);

        $this->postJson('/api/v1/suppliers', supplierPayload([
            'is_active' => false,
        ]))
            ->assertCreated()
            ->assertJsonPath('data.is_active', false)
            ->assertJsonPath('data.status', 'Inactive');
    });

    it('returns 422 when required fields are missing', function () {
        actingAsTenantUser(permissions: [Permission::SuppliersCreate]);

        $this->postJson('/api/v1/suppliers', [])
            ->assertUnprocessable()
            ->assertJsonPath('errors.name.0', 'The name field is required.')
            ->assertJsonValidationErrors(['name', 'email', 'phone', 'address']);
    });

    it('returns 422 when the email is invalid', function () {
        actingAsTenantUser(permissions: [Permission::SuppliersCreate]);

        $this->postJson('/api/v1/suppliers', supplierPayload([
            'email' => 'not-an-email',
        ]))
            ->assertUnprocessable()
            ->assertJsonPath('errors.email.0', 'The email field must be a valid email address.');
    });
});

describe('update and destroy', function () {
    it('updates the supplier', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::SuppliersUpdate]);
        $supplier = Supplier::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Old',
        ]);

        $this->putJson('/api/v1/suppliers/'.$supplier->id, supplierPayload([
            'name' => 'Updated Supplies',
            'email' => 'updated@acme.test',
        ]))
            ->assertOk()
            ->assertJsonPath('data.name', 'Updated Supplies')
            ->assertJsonPath('data.email', 'updated@acme.test');

        $this->assertDatabaseHas('suppliers', [
            'id' => $supplier->id,
            'name' => 'Updated Supplies',
            'email' => 'updated@acme.test',
            'tenant_id' => $tenant->id,
        ]);
    });

    it('returns 404 when updating another tenants supplier', function () {
        $tenantA = Tenant::factory()->create();
        actingAsTenantUser($tenantA, [Permission::SuppliersUpdate]);
        $foreign = Supplier::withoutEvents(fn () => Supplier::factory()->create());

        $this->putJson('/api/v1/suppliers/'.$foreign->id, supplierPayload())
            ->assertNotFound();
    });

    it('deletes the supplier', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::SuppliersDelete]);
        $supplier = Supplier::factory()->create(['tenant_id' => $tenant->id]);

        $this->postJson('/api/v1/suppliers/delete', [
            'action' => 'delete',
            'ids' => [$supplier->id],
        ])->assertOk()
            ->assertJsonPath('message', 'Supplier deleted successfully');

        $this->assertDatabaseMissing('suppliers', ['id' => $supplier->id]);
    });

    it('rejects deleting a supplier that is used on a purchase', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::SuppliersDelete]);
        $supplier = Supplier::factory()->create(['tenant_id' => $tenant->id]);
        Purchase::factory()->create([
            'tenant_id' => $tenant->id,
            'supplier_id' => $supplier->id,
        ]);

        $this->postJson('/api/v1/suppliers/delete', [
            'action' => 'delete',
            'ids' => [$supplier->id],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors([
                'ids' => 'This supplier cannot be deleted because it is used on a purchase.',
            ]);

        $this->assertDatabaseHas('suppliers', ['id' => $supplier->id]);
    });

    it('rejects a delete request with an invalid action', function () {
        actingAsTenantUser(permissions: [Permission::SuppliersDelete]);

        $this->postJson('/api/v1/suppliers/delete', [
            'action' => 'archive',
            'ids' => [1],
        ])->assertStatus(400);
    });
});
