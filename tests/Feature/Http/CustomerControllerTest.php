<?php

use App\Enums\Permission;
use App\Models\User;
use DA\Admin\Models\Tenant;
use DA\Inventory\Models\Customer;

describe('authentication and authorization', function () {
    it('returns 401 when no token is provided', function (string $method, string $uri) {
        $this->json($method, $uri, customerPayload())->assertUnauthorized();
    })->with([
        ['GET', '/api/v1/customers'],
        ['POST', '/api/v1/customers'],
        ['GET', '/api/v1/customers/1'],
        ['PUT', '/api/v1/customers/1'],
        ['POST', '/api/v1/customers/delete'],
    ]);

    it('returns 403 when the user lacks the required permission', function () {
        actingAsTenantUser(permissions: [Permission::SuppliersView]);

        $this->getJson('/api/v1/customers')->assertForbidden();
    });
});

describe('index and show', function () {
    it('lists customers for the current tenant newest first', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::CustomersView]);
        $older = Customer::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Older']);
        $newer = Customer::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Newer']);
        Customer::withoutEvents(fn () => Customer::factory()->create([
            'name' => 'Other tenant',
        ]));

        $this->getJson('/api/v1/customers')
            ->assertOk()
            ->assertJsonPath('data.0.id', $newer->id)
            ->assertJsonPath('data.1.id', $older->id)
            ->assertJsonMissing(['name' => 'Other tenant']);
    });

    it('filters customers by name, email, and phone', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::CustomersView]);
        Customer::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Nadia Rahman',
            'email' => 'nadia@shop.test',
            'phone' => '01711111111',
        ]);
        Customer::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Karim Ali',
            'email' => 'karim@shop.test',
            'phone' => '01822222222',
        ]);

        $this->getJson('/api/v1/customers?name=Nadia&limit=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Nadia Rahman')
            ->assertJsonPath('meta.per_page', 1);

        $this->getJson('/api/v1/customers?email=nadia@')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.email', 'nadia@shop.test');

        $this->getJson('/api/v1/customers?phone=01822')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.phone', '01822222222');
    });

    it('returns 404 when tenant A requests a tenant B customer', function () {
        $tenantA = Tenant::factory()->create();
        actingAsTenantUser($tenantA, [Permission::CustomersView]);
        $foreign = Customer::withoutEvents(fn () => Customer::factory()->create());

        $this->getJson('/api/v1/customers/'.$foreign->id)->assertNotFound();
    });
});

describe('store', function () {
    it('creates a customer for the authenticated tenant', function () {
        $tenant = Tenant::factory()->create();
        $user = actingAsTenantUser($tenant, [Permission::CustomersCreate]);

        $this->postJson('/api/v1/customers', customerPayload([
            'address' => null,
        ]))
            ->assertCreated()
            ->assertJsonPath('data.name', 'Nadia Rahman')
            ->assertJsonPath('data.phone', '01711111111')
            ->assertJsonPath('data.email', 'nadia@shop.test')
            ->assertJsonPath('data.address', null)
            ->assertJsonPath('data.created_by', $user->id)
            ->assertJsonPath('data.updated_by', $user->id);

        $this->assertDatabaseHas('customers', [
            'name' => 'Nadia Rahman',
            'phone' => '01711111111',
            'email' => 'nadia@shop.test',
            'tenant_id' => $tenant->id,
            'address' => null,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
    });

    it('returns 422 when required fields are missing', function () {
        actingAsTenantUser(permissions: [Permission::CustomersCreate]);

        $this->postJson('/api/v1/customers', [])
            ->assertUnprocessable()
            ->assertJsonPath('errors.name.0', 'The name field is required.')
            ->assertJsonPath('errors.phone.0', 'The phone field is required.')
            ->assertJsonPath('errors.email.0', 'The email field is required.')
            ->assertJsonValidationErrors(['name', 'phone', 'email']);
    });

    it('returns 422 when the email is invalid', function () {
        actingAsTenantUser(permissions: [Permission::CustomersCreate]);

        $this->postJson('/api/v1/customers', customerPayload([
            'email' => 'not-an-email',
        ]))
            ->assertUnprocessable()
            ->assertJsonPath('errors.email.0', 'The email field must be a valid email address.');
    });
});

describe('update and destroy', function () {
    it('updates the customer and keeps the original creator', function () {
        $tenant = Tenant::factory()->create();
        $creator = User::factory()->forTenant($tenant)->create();
        $editor = actingAsTenantUser($tenant, [Permission::CustomersUpdate]);
        $customer = Customer::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Old',
            'created_by' => $creator->id,
            'updated_by' => $creator->id,
        ]);

        $this->putJson('/api/v1/customers/'.$customer->id, customerPayload([
            'name' => 'Updated Customer',
            'email' => 'updated@shop.test',
        ]))
            ->assertOk()
            ->assertJsonPath('data.name', 'Updated Customer')
            ->assertJsonPath('data.email', 'updated@shop.test')
            ->assertJsonPath('data.created_by', $creator->id)
            ->assertJsonPath('data.updated_by', $editor->id);

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'name' => 'Updated Customer',
            'email' => 'updated@shop.test',
            'tenant_id' => $tenant->id,
            'created_by' => $creator->id,
            'updated_by' => $editor->id,
        ]);
    });

    it('returns 404 when updating another tenants customer', function () {
        $tenantA = Tenant::factory()->create();
        actingAsTenantUser($tenantA, [Permission::CustomersUpdate]);
        $foreign = Customer::withoutEvents(fn () => Customer::factory()->create());

        $this->putJson('/api/v1/customers/'.$foreign->id, customerPayload())
            ->assertNotFound();
    });

    it('deletes the customer', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::CustomersDelete]);
        $customer = Customer::factory()->create(['tenant_id' => $tenant->id]);

        $this->postJson('/api/v1/customers/delete', [
            'action' => 'delete',
            'ids' => [$customer->id],
        ])->assertOk()
            ->assertJsonPath('message', 'Customer deleted successfully');

        $this->assertDatabaseMissing('customers', ['id' => $customer->id]);
    });

    it('returns 404 when deleting another tenants customer', function () {
        $tenantA = Tenant::factory()->create();
        actingAsTenantUser($tenantA, [Permission::CustomersDelete]);
        $foreign = Customer::withoutEvents(fn () => Customer::factory()->create());

        $this->postJson('/api/v1/customers/delete', [
            'action' => 'delete',
            'ids' => [$foreign->id],
        ])->assertNotFound();

        $this->assertDatabaseHas('customers', ['id' => $foreign->id]);
    });

    it('rejects a delete request with an invalid action', function () {
        actingAsTenantUser(permissions: [Permission::CustomersDelete]);

        $this->postJson('/api/v1/customers/delete', [
            'action' => 'archive',
            'ids' => [1],
        ])->assertStatus(400);
    });
});
