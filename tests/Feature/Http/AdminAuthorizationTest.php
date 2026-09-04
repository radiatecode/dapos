<?php

use App\Enums\UserRole;
use App\Models\User;
use DA\Admin\Enums\AdminPermission;
use DA\Admin\Models\AdminUser;

describe('guests and POS users', function () {
    it('redirects guests from provider admin pages to login', function (string $uri) {
        $this->get($uri)->assertRedirect(route('admin.login'));
    })->with([
        '/admin',
        '/admin/profile',
        '/admin/permissions',
        '/admin/tenants',
        '/admin/tenants/create',
    ]);

    it('redirects a POS user on the web guard away from provider admin pages', function () {
        $posUser = User::factory()->create([
            'role' => UserRole::TenantUser,
            'tenant_id' => null,
        ]);

        $this->actingAs($posUser)
            ->get('/admin')
            ->assertRedirect(route('admin.login'));
    });

    it('forbids a POS user forced onto the admin guard', function () {
        $posUser = User::factory()->create([
            'role' => UserRole::TenantUser,
            'tenant_id' => null,
        ]);

        $this->actingAs($posUser, 'admin')
            ->get('/admin')
            ->assertForbidden();
    });
});

describe('dashboard and profile', function () {
    it('renders the dashboard for a platform admin', function () {
        actingAsPlatformAdmin();

        $this->get('/admin')
            ->assertOk()
            ->assertSee('Dashboard')
            ->assertSee('SaaS control plane');
    });

    it('renders the profile page for a platform admin', function () {
        $admin = actingAsPlatformAdmin();

        $this->get('/admin/profile')
            ->assertOk()
            ->assertSee('Profile')
            ->assertSee($admin->email);
    });

    it('updates profile information', function () {
        $admin = actingAsPlatformAdmin();

        $this->put('/admin/profile/information', [
            'name' => 'Updated Admin',
            'email' => 'updated@radiate.test',
        ])->assertRedirect();

        $this->assertDatabaseHas('admin_users', [
            'id' => $admin->id,
            'name' => 'Updated Admin',
            'email' => 'updated@radiate.test',
        ]);
    });

    it('returns a validation error when the profile name is missing', function () {
        actingAsPlatformAdmin();

        $this->from('/admin/profile')
            ->put('/admin/profile/information', [
                'name' => '',
                'email' => 'updated@radiate.test',
            ])
            ->assertRedirect('/admin/profile')
            ->assertSessionHasErrors(['name'], errorBag: 'updateProfileInformation');
    });

    it('escapes a dangerous admin name in the shell', function () {
        $admin = AdminUser::factory()->create([
            'name' => '<script>alert("xss")</script>',
        ]);
        grantAdminPermissions($admin, [AdminPermission::PermissionsView]);
        $this->actingAs($admin, 'admin');

        $this->get('/admin/profile')
            ->assertOk()
            ->assertDontSee('<script>alert("xss")</script>', false);
    });
});

describe('permissions', function () {
    it('renders granted and withheld permissions for the signed-in admin', function () {
        seedAdminPermissionCatalog();

        $admin = actingAsPlatformAdmin([
            AdminPermission::PermissionsView,
            AdminPermission::TenantsView,
        ]);

        $this->get('/admin/permissions')
            ->assertOk()
            ->assertSee('View permissions')
            ->assertSee('View tenants')
            ->assertSee('Granted')
            ->assertSee('Hidden');
    });

    it('forbids the permissions page when the admin lacks permissions.view', function () {
        actingAsPlatformAdmin([AdminPermission::TenantsView]);

        $this->get('/admin/permissions')->assertForbidden();
    });

    it('hides navigation items the admin cannot access', function () {
        actingAsPlatformAdmin([AdminPermission::PermissionsView]);

        $this->get('/admin')
            ->assertOk()
            ->assertSee('Permissions')
            ->assertDontSee('Tenants');
    });

    it('shows permitted navigation items including upcoming modules', function () {
        actingAsPlatformAdmin([
            AdminPermission::PermissionsView,
            AdminPermission::TenantsView,
        ]);

        $this->get('/admin')
            ->assertOk()
            ->assertSee('Tenants')
            ->assertSee('Soon');
    });
});
