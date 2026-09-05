<?php

use DA\Admin\Enums\AdminPermission;
use DA\Admin\Models\AdminUser;

describe('admin chrome', function () {
    it('renders the colored sidebar, top bar, and theme toggle for a platform admin', function () {
        actingAsPlatformAdmin();

        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('admin-sidebar', false)
            ->assertSee('admin-topbar', false)
            ->assertSee('id="theme-mode"', false)
            ->assertSee('id="theme-mode-icon"', false)
            ->assertSee('data-theme="dark"', false)
            ->assertSee("theme = 'dark'", false)
            ->assertSee('Switch to light mode')
            ->assertSee('fa-sun', false)
            ->assertSee('admin-theme', false)
            ->assertSee(route('admin.dashboard'), false)
            ->assertSee(route('admin.profile'), false)
            ->assertDontSee('../../index3.html', false)
            ->assertDontSee('Brad Diesel', false);
    });

    it('escapes a dangerous admin name in the sidebar and top bar', function () {
        $admin = AdminUser::factory()->create([
            'name' => '<script>alert("xss")</script>',
        ]);
        grantAdminPermissions($admin, AdminPermission::cases());
        $this->actingAs($admin, 'admin');

        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert("xss")</script>', false);
    });
});
