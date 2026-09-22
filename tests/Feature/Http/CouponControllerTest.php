<?php

use DA\Admin\Enums\AdminPermission;
use DA\Admin\Enums\CouponDiscountType;
use DA\Admin\Models\Coupon;

describe('index', function () {
    it('redirects guests from the coupon list to login', function () {
        $this->get(route('admin.coupons.index'))
            ->assertRedirect(route('admin.login'));
    });

    it('forbids an admin without coupons.view', function () {
        actingAsPlatformAdmin([AdminPermission::InvoicesView]);

        $this->get(route('admin.coupons.index'))->assertForbidden();
    });

    it('renders the coupon datatable and create modal', function () {
        actingAsPlatformAdmin();

        $this->get(route('admin.coupons.index'))
            ->assertOk()
            ->assertSee('coupons-table', false)
            ->assertSee('coupon-form-modal', false)
            ->assertSee('Create coupon');
    });
});

describe('store and update', function () {
    it('forbids coupon creation without coupons.manage', function () {
        actingAsPlatformAdmin([AdminPermission::CouponsView]);

        $this->post(route('admin.coupons.store'), couponPayload())
            ->assertForbidden();
    });

    it('creates a percentage coupon from an ajax request', function () {
        actingAsPlatformAdmin();

        $this->from(route('admin.coupons.index'))
            ->post(route('admin.coupons.store'), couponPayload(), [
                'X-Requested-With' => 'XMLHttpRequest',
                'Accept' => 'application/json',
            ])
            ->assertCreated()
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseHas('coupons', [
            'code' => 'SAVE10',
            'discount_type' => CouponDiscountType::Percentage->value,
            'discount_value' => '10.00',
            'is_active' => 1,
        ]);
    });

    it('returns a validation error when the coupon code is missing', function () {
        actingAsPlatformAdmin();

        $this->from(route('admin.coupons.index'))
            ->post(route('admin.coupons.store'), couponPayload(['code' => '']))
            ->assertRedirect(route('admin.coupons.index'))
            ->assertSessionHasErrors(['code']);
    });

    it('updates a coupon from an ajax request', function () {
        actingAsPlatformAdmin();
        $coupon = Coupon::factory()->create(['code' => 'OLD10', 'name' => 'Old']);

        $this->from(route('admin.coupons.index'))
            ->put(route('admin.coupons.update', $coupon), couponPayload([
                'code' => 'NEW10',
                'name' => 'New launch',
            ]), [
                'X-Requested-With' => 'XMLHttpRequest',
                'Accept' => 'application/json',
            ])
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseHas('coupons', [
            'id' => $coupon->id,
            'code' => 'NEW10',
            'name' => 'New launch',
        ]);
    });
});
