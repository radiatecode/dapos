<?php

namespace DA\Admin\Database\Factories;

use DA\Admin\Models\Coupon;
use DA\Admin\Models\CouponRedemption;
use DA\Admin\Models\Invoice;
use DA\Admin\Models\Subscription;
use DA\Admin\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CouponRedemption>
 */
class CouponRedemptionFactory extends Factory
{
    protected $model = CouponRedemption::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'coupon_id' => Coupon::factory(),
            'tenant_id' => Tenant::factory(),
            'invoice_id' => Invoice::factory(),
            'subscription_id' => Subscription::factory(),
            'discount_amount' => '5.00',
            'redeemed_at' => now(),
        ];
    }
}
