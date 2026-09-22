<?php

use App\Enums\Permission;
use App\Models\Role;
use DA\Admin\Models\Tenant;

describe('authentication and authorization', function () {
    it('returns 401 when no token is provided', function (string $method, string $uri) {
        $this->json($method, $uri, rolePayload())->assertUnauthorized();
    })->with([
        ['GET', '/api/v1/roles'],
        ['POST', '/api/v1/roles'],
        ['GET', '/api/v1/roles/1'],
        ['PUT', '/api/v1/roles/1'],
        ['DELETE', '/api/v1/roles/1'],
        ['GET', '/api/v1/permissions'],
    ]);

    it('returns 403 when the user lacks the required permission', function () {
        actingAsTenantUser(permissions: [Permission::UsersView]);

        $this->getJson('/api/v1/roles')->assertForbidden();
    });
});

describe('index and show', function () {
    it('lists roles for the current tenant newest first', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::RolesView]);
        $older = Role::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Older']);
        $newer = Role::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Newer']);
        Role::factory()->create(['name' => 'Other tenant']);

        $this->getJson('/api/v1/roles')
            ->assertOk()
            ->assertJsonPath('data.0.id', $newer->id)
            ->assertJsonPath('data.1.id', $older->id)
            ->assertJsonMissing(['name' => 'Other tenant']);
    });

    it('returns 404 when tenant A requests a tenant B role', function () {
        $tenantA = Tenant::factory()->create();
        actingAsTenantUser($tenantA, [Permission::RolesView]);
        $foreign = Role::factory()->create();

        $this->getJson('/api/v1/roles/'.$foreign->id)->assertNotFound();
    });

    it('returns the permission catalog', function () {
        actingAsTenantUser(permissions: [Permission::RolesView]);

        $this->getJson('/api/v1/permissions')
            ->assertOk()
            ->assertJsonFragment([
                'key' => Permission::UsersCreate->value,
                'name' => Permission::UsersCreate->label(),
                'group' => Permission::UsersCreate->group(),
            ]);
    });
});

describe('store', function () {
    it('creates a role and generates a slug from the name', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::RolesCreate]);

        $this->postJson('/api/v1/roles', [
            'name' => 'Store Manager',
            'permissions' => [Permission::SalesView->value, Permission::UsersView->value],
        ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Store Manager')
            ->assertJsonPath('data.slug', 'store-manager')
            ->assertJsonPath('data.permissions.0', Permission::SalesView->value);

        $this->assertDatabaseHas('roles', [
            'name' => 'Store Manager',
            'slug' => 'store-manager',
            'tenant_id' => $tenant->id,
        ]);
    });

    it('returns 422 when the name is missing', function () {
        actingAsTenantUser(permissions: [Permission::RolesCreate]);

        $this->postJson('/api/v1/roles', rolePayload(['name' => '']))
            ->assertUnprocessable()
            ->assertJsonPath('errors.name.0', 'The name field is required.');
    });

    it('returns 422 when the slug is already taken', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::RolesCreate]);
        Role::factory()->create([
            'tenant_id' => $tenant->id,
            'slug' => 'cashier',
        ]);

        $this->postJson('/api/v1/roles', rolePayload())
            ->assertUnprocessable()
            ->assertJsonPath('errors.slug.0', 'The slug has already been taken.');
    });

    it('returns 422 when a permission is invalid', function () {
        actingAsTenantUser(permissions: [Permission::RolesCreate]);

        $this->postJson('/api/v1/roles', rolePayload([
            'permissions' => ['not-a-permission'],
        ]))->assertUnprocessable()
            ->assertJsonValidationErrors(['permissions.0']);
    });
});

describe('update and destroy', function () {
    it('updates the role', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::RolesUpdate]);
        $role = Role::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Old',
            'slug' => 'old',
            'permissions' => [Permission::SalesView->value],
        ]);

        $this->putJson('/api/v1/roles/'.$role->id, [
            'name' => 'Updated',
            'slug' => 'updated',
            'permissions' => [Permission::ReportsView->value],
        ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Updated')
            ->assertJsonPath('data.slug', 'updated')
            ->assertJsonPath('data.permissions.0', Permission::ReportsView->value);

        $this->assertDatabaseHas('roles', [
            'id' => $role->id,
            'name' => 'Updated',
            'slug' => 'updated',
        ]);
    });

    it('deletes the role', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::RolesDelete]);
        $role = Role::factory()->create(['tenant_id' => $tenant->id]);

        $this->deleteJson('/api/v1/roles/'.$role->id)->assertNoContent();

        $this->assertDatabaseMissing('roles', ['id' => $role->id]);
    });
});
