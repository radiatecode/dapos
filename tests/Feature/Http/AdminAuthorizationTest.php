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
        '/admin/plans',
        '/admin/plans/create',
        '/admin/features',
        '/admin/addons',
        '/admin/subscriptions',
        '/admin/subscriptions/create',
        '/admin/subscription-events',
        '/admin/invoices',
        '/admin/invoices/create',
        '/admin/payments',
        '/admin/coupons',
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
            ->assertSee('Dashboard');
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
            ->assertSee(route('admin.permissions'), false)
            ->assertDontSee('Tenants')
            ->assertDontSee('Tenancy')
            ->assertDontSee(route('admin.tenants.index'), false);
    });

    it('renders a single nav link for items without children', function () {
        actingAsPlatformAdmin([AdminPermission::PermissionsView]);

        $this->get('/admin')
            ->assertOk()
            ->assertSee('Dashboard')
            ->assertSee(route('admin.dashboard'), false)
            ->assertDontSee('has-treeview', false);
    });

    it('renders both tenant children when the tenants tree is visible', function () {
        actingAsPlatformAdmin([AdminPermission::TenantsView]);

        $this->get('/admin')
            ->assertOk()
            ->assertSee('has-treeview', false)
            ->assertSee('Tenancy')
            ->assertSee('Tenants')
            ->assertSee('List')
            ->assertSee('New')
            ->assertSee(route('admin.tenants.index'), false)
            ->assertSee(route('admin.tenants.create'), false);
    });

    it('hides a nav header when none of its items are visible', function () {
        actingAsPlatformAdmin([AdminPermission::PermissionsView]);

        $this->get('/admin')
            ->assertOk()
            ->assertSee('Overview')
            ->assertSee('System')
            ->assertDontSee('Tenancy')
            ->assertDontSee('Catalog');
    });

    it('opens the tenants tree on the tenant create page', function () {
        actingAsPlatformAdmin([
            AdminPermission::TenantsView,
            AdminPermission::TenantsCreate,
        ]);

        $this->get(route('admin.tenants.create'))
            ->assertOk()
            ->assertSee('menu-open', false)
            ->assertSee(route('admin.tenants.create'), false);
    });
});
