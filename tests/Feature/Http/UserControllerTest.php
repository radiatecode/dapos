<?php

use App\Enums\Permission;
use App\Models\Role;
use App\Models\User;
use DA\Admin\Models\Tenant;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

describe('authentication and authorization', function () {
    it('returns 401 when no token is provided', function (string $method, string $uri) {
        $this->json($method, $uri, tenantUserPayload())->assertUnauthorized();
    })->with([
        ['GET', '/api/v1/users'],
        ['POST', '/api/v1/users'],
        ['GET', '/api/v1/users/1'],
        ['PUT', '/api/v1/users/1'],
        ['DELETE', '/api/v1/users/1'],
        ['PUT', '/api/v1/users/1/password'],
        ['POST', '/api/v1/users/1/photo'],
        ['PUT', '/api/v1/users/1/role'],
    ]);

    it('returns 403 when the user lacks the required permission', function () {
        actingAsTenantUser(permissions: [Permission::RolesView]);

        $this->getJson('/api/v1/users')->assertForbidden();
    });

    it('returns 403 when an inactive super admin requests users', function () {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->forTenant($tenant)->create([
            'is_super_admin' => true,
            'is_active' => false,
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/users')->assertForbidden();
    });

    it('returns 403 when a super admin belongs to an inactive tenant', function () {
        $tenant = Tenant::factory()->inactive()->create();
        $user = User::factory()->forTenant($tenant)->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/users')->assertForbidden();
    });

    it('returns users for an active super admin of an active tenant', function () {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->forTenant($tenant)->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/users')->assertOk();
    });
});

describe('index and show', function () {
    it('lists users for the current tenant newest first', function () {
        $tenant = Tenant::factory()->create();
        $actor = actingAsTenantUser($tenant, [Permission::UsersView]);
        $older = User::factory()->forTenant($tenant)->create(['name' => 'Older']);
        $newer = User::factory()->forTenant($tenant)->create(['name' => 'Newer']);
        $other = Tenant::factory()->create();
        User::factory()->forTenant($other)->create(['name' => 'Other tenant']);

        $response = $this->getJson('/api/v1/users');

        $response
            ->assertOk()
            ->assertJsonPath('data.0.id', $newer->id)
            ->assertJsonPath('data.1.id', $older->id)
            ->assertJsonPath('meta.total', 3)
            ->assertJsonMissing(['name' => 'Other tenant']);

        expect(collect($response->json('data'))->pluck('id'))->toContain($actor->id);
    });

    it('includes the assigned role and honors the data table limit', function () {
        $tenant = Tenant::factory()->create();
        User::factory()->forTenant($tenant)->count(2)->create();
        $actor = actingAsTenantUser($tenant, [Permission::UsersView]);

        $this->getJson('/api/v1/users?limit=1')
            ->assertOk()
            ->assertJsonPath('meta.per_page', 1)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $actor->id)
            ->assertJsonPath('data.0.role.id', $actor->role_id);
    });

    it('returns 404 when tenant A requests a tenant B user', function () {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();
        actingAsTenantUser($tenantA, [Permission::UsersView]);
        $foreign = User::factory()->forTenant($tenantB)->create();

        $this->getJson('/api/v1/users/'.$foreign->id)->assertNotFound();
    });
});

describe('store', function () {
    it('creates a tenant-scoped user', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::UsersCreate]);
        $role = Role::factory()->create([
            'tenant_id' => $tenant->id,
            'permissions' => [Permission::SalesView->value],
        ]);

        $this->postJson('/api/v1/users', tenantUserPayload([
            'role_id' => $role->id,
        ]))
            ->assertCreated()
            ->assertJsonPath('data.name', 'Cashier One')
            ->assertJsonPath('data.email', 'cashier@shop.test')
            ->assertJsonPath('data.role.id', $role->id);

        $this->assertDatabaseHas('users', [
            'email' => 'cashier@shop.test',
            'tenant_id' => $tenant->id,
            'role' => 'tenant_user',
        ]);
    });

    it('returns 422 when the name is missing', function () {
        actingAsTenantUser(permissions: [Permission::UsersCreate]);

        $this->postJson('/api/v1/users', tenantUserPayload(['name' => '']))
            ->assertUnprocessable()
            ->assertJsonPath('errors.name.0', 'The name field is required.');
    });

    it('returns 422 when the email is already taken', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::UsersCreate]);
        User::factory()->forTenant($tenant)->create(['email' => 'cashier@shop.test']);

        $this->postJson('/api/v1/users', tenantUserPayload())
            ->assertUnprocessable()
            ->assertJsonPath('errors.email.0', 'The email has already been taken.');
    });

    it('returns 422 when a role belongs to another tenant', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::UsersCreate]);
        $foreignRole = Role::factory()->create();

        $this->postJson('/api/v1/users', tenantUserPayload([
            'role_id' => $foreignRole->id,
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['role_id']);
    });
});

describe('update and destroy', function () {
    it('updates the user', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::UsersUpdate]);
        $user = User::factory()->forTenant($tenant)->create([
            'name' => 'Old Name',
            'email' => 'old@shop.test',
        ]);

        $this->putJson('/api/v1/users/'.$user->id, [
            'name' => 'New Name',
            'email' => 'new@shop.test',
        ])
            ->assertOk()
            ->assertJsonPath('data.name', 'New Name')
            ->assertJsonPath('data.email', 'new@shop.test');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'New Name',
            'email' => 'new@shop.test',
        ]);
    });

    it('deletes the user', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::UsersDelete]);
        $user = User::factory()->forTenant($tenant)->create();

        $this->deleteJson('/api/v1/users/'.$user->id)->assertNoContent();

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    });

    it('lets a user update their own profile without the users.update permission', function () {
        $user = actingAsTenantUser(permissions: [Permission::SalesView]);

        $this->putJson('/api/v1/users/'.$user->id, [
            'name' => 'Renamed Owner',
            'email' => 'renamed@shop.test',
        ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Renamed Owner')
            ->assertJsonPath('data.email', 'renamed@shop.test');
    });

    it('forbids a user without users.update from updating someone else', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::SalesView]);
        $other = User::factory()->forTenant($tenant)->create();

        $this->putJson('/api/v1/users/'.$other->id, [
            'name' => 'Hijack',
            'email' => 'hijack@shop.test',
        ])->assertForbidden();
    });

    it('lets a user change their own password without the users.update permission', function () {
        $user = actingAsTenantUser(permissions: [Permission::SalesView]);

        $this->putJson('/api/v1/users/'.$user->id.'/password', [
            'current_password' => 'password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertOk();

        expect(Hash::check('new-password', $user->fresh()->password))->toBeTrue();
    });

    it('returns 403 when deleting the authenticated user', function () {
        $user = actingAsTenantUser(permissions: [Permission::UsersDelete]);

        $this->deleteJson('/api/v1/users/'.$user->id)->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $user->id]);
    });
});

describe('password photo and roles', function () {
    it('updates another users password without the current password', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::UsersUpdate]);
        $user = User::factory()->forTenant($tenant)->create([
            'password' => 'password',
        ]);

        $this->putJson('/api/v1/users/'.$user->id.'/password', [
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertOk();

        expect(Hash::check('new-password', $user->fresh()->password))->toBeTrue();
    });

    it('returns 422 when updating own password without the current password', function () {
        $user = actingAsTenantUser(permissions: [Permission::UsersUpdate]);

        $this->putJson('/api/v1/users/'.$user->id.'/password', [
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])
            ->assertUnprocessable()
            ->assertJsonPath('errors.current_password.0', 'The current password field is required.');
    });

    it('stores an uploaded photo', function () {
        Storage::fake('public');

        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::UsersUpdate]);
        $user = User::factory()->forTenant($tenant)->create();
        $photo = UploadedFile::fake()->image('avatar.jpg');

        $response = $this->post('/api/v1/users/'.$user->id.'/photo', [
            'photo' => $photo,
        ], ['Accept' => 'application/json']);

        $response->assertOk();

        $path = $response->json('data.photo');

        expect($path)->toBeString();
        Storage::disk('public')->assertExists($path);
    });

    it('assigns roles to a user', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::UsersUpdate]);
        $user = User::factory()->forTenant($tenant)->create();
        $role = Role::factory()->create([
            'tenant_id' => $tenant->id,
            'permissions' => [Permission::SalesView->value],
        ]);

        $this->putJson('/api/v1/users/'.$user->id.'/role', [
            'role_id' => $role->id,
        ])
            ->assertOk()
            ->assertJsonPath('data.role.id', $role->id)
            ->assertJsonPath('data.permissions.0', Permission::SalesView->value);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'role_id' => $role->id,
        ]);
    });
});
