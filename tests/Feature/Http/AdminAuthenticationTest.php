<?php

use App\Enums\UserRole;
use App\Models\User;
use DA\Admin\Models\AdminUser;

describe('login', function () {
    it('renders the provider admin login page', function () {
        $this->get('/admin/login')
            ->assertOk()
            ->assertSee('Sign in to admin')
            ->assertSee('Provider control plane')
            ->assertSee('/admin/login', false)
            ->assertSee(asset('vendor/admin/style.css'), false);
    });

    it('authenticates an admin user and redirects to the dashboard', function () {
        $admin = AdminUser::factory()->create([
            'email' => 'owner@radiate.test',
            'password' => 'password',
        ]);

        $this->from('/admin/login')
            ->post('/admin/login', [
                'email' => 'owner@radiate.test',
                'password' => 'password',
            ])
            ->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($admin, 'admin');
        $this->assertDatabaseHas('admin_users', ['email' => 'owner@radiate.test']);
        $this->assertDatabaseMissing('users', ['email' => 'owner@radiate.test']);
    });

    it('rejects a POS user with valid credentials', function () {
        User::factory()->create([
            'email' => 'cashier@shop.test',
            'password' => 'password',
            'role' => UserRole::TenantUser,
            'tenant_id' => null,
        ]);

        $this->from('/admin/login')
            ->post('/admin/login', [
                'email' => 'cashier@shop.test',
                'password' => 'password',
            ])
            ->assertRedirect('/admin/login')
            ->assertSessionHasErrors(['email' => trans('auth.failed')]);

        $this->assertGuest('admin');
    });

    it('rejects invalid credentials', function () {
        AdminUser::factory()->create([
            'email' => 'owner@radiate.test',
            'password' => 'password',
        ]);

        $this->from('/admin/login')
            ->post('/admin/login', [
                'email' => 'owner@radiate.test',
                'password' => 'wrong-password',
            ])
            ->assertRedirect('/admin/login')
            ->assertSessionHasErrors(['email' => trans('auth.failed')]);

        $this->assertGuest('admin');
    });

    it('returns validation errors when the email is missing', function () {
        $this->from('/admin/login')
            ->post('/admin/login', [
                'password' => 'password',
            ])
            ->assertRedirect('/admin/login')
            ->assertSessionHasErrors(['email']);
    });

    it('renders the login form at login and api login urls', function (string $uri) {
        $this->get($uri)
            ->assertOk()
            ->assertSee('Sign in to admin');
    })->with([
        '/login',
        '/api/login',
    ]);

    it('redirects an authenticated admin away from the login page', function () {
        actingAsPlatformAdmin();

        $this->get('/admin/login')->assertRedirect(route('admin.dashboard'));
    });
});

describe('logout', function () {
    it('ends the admin session and returns to login', function () {
        actingAsPlatformAdmin();

        $this->post('/admin/logout')->assertRedirect(route('admin.login'));

        $this->assertGuest('admin');
        $this->get('/admin')->assertRedirect(route('admin.login'));
    });
});
