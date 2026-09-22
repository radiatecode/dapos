<?php

namespace DA\Admin\Services;

use DA\Admin\DTO\CouponDTO;
use DA\Admin\Exceptions\BillingException;
use DA\Admin\Models\Coupon;
use DA\Admin\Models\CouponRedemption;
use DA\Admin\Models\Invoice;
use DA\Admin\Models\Subscription;
use DA\Admin\Models\Tenant;
use Illuminate\Support\Str;

class CouponService
{
    public function create(CouponDTO $dto): Coupon
    {
        $coupon = new Coupon;
        $this->fill($coupon, $dto);
        $coupon->save();

        return $coupon;
    }

    public function update(Coupon $coupon, CouponDTO $dto): Coupon
    {
        $this->fill($coupon, $dto);
        $coupon->save();

        return $coupon->refresh();
    }

    public function validate(string $code, Tenant $tenant, Subscription $subscription, string $subtotal): Coupon
    {
        $coupon = Coupon::queries()->findByCode(Str::upper(trim($code)));

        if (! $coupon instanceof Coupon) {
            throw new BillingException('This coupon code is not valid.');
        }

        if (! $coupon->isActive()) {
            throw new BillingException('This coupon is not active.');
        }

        if ($coupon->starts_at !== null && $coupon->starts_at->isFuture()) {
            throw new BillingException('This coupon is not valid yet.');
        }

        if ($coupon->ends_at !== null && $coupon->ends_at->isPast()) {
            throw new BillingException('This coupon has expired.');
        }

        if ($coupon->isFixed() && $coupon->currency_id !== null && $coupon->currency_id !== $subscription->plan?->currency_id) {
            throw new BillingException('This coupon does not apply to the subscription currency.');
        }

        if ($coupon->minimum_amount !== null && $this->asFloat($subtotal) < $this->asFloat($coupon->minimum_amount)) {
            throw new BillingException('This coupon requires a higher invoice subtotal.');
        }

        if ($coupon->max_redemptions !== null) {
            $used = CouponRedemption::queries()->countForCoupon($coupon->id);

            if ($used >= $coupon->max_redemptions) {
                throw new BillingException('This coupon has reached its usage limit.');
            }
        }

        if ($coupon->max_redemptions_per_tenant !== null) {
            $usedByTenant = CouponRedemption::queries()->countForCouponAndTenant($coupon->id, $tenant->id);

            if ($usedByTenant >= $coupon->max_redemptions_per_tenant) {
                throw new BillingException('This tenant has already used this coupon the allowed number of times.');
            }
        }

        return $coupon;
    }

    public function discountFor(Coupon $coupon, string $subtotal): string
    {
        $base = $this->asFloat($subtotal);

        if ($base <= 0) {
            return '0.00';
        }

        if ($coupon->isPercentage()) {
            $discount = $base * ($this->asFloat($coupon->discount_value) / 100);
        } else {
            $discount = $this->asFloat($coupon->discount_value);
        }

        return $this->money(min($discount, $base));
    }

    public function redeem(Coupon $coupon, Invoice $invoice, string $discountAmount): CouponRedemption
    {
        $redemption = new CouponRedemption;
        $redemption->coupon_id = $coupon->id;
        $redemption->tenant_id = $invoice->tenant_id;
        $redemption->invoice_id = $invoice->id;
        $redemption->subscription_id = $invoice->subscription_id;
        $redemption->discount_amount = $this->money($discountAmount);
        $redemption->redeemed_at = now();
        $redemption->save();

        return $redemption;
    }

    private function fill(Coupon $coupon, CouponDTO $dto): void
    {
        $coupon->code = $dto->code;
        $coupon->name = $dto->name;
        $coupon->description = $dto->description;
        $coupon->discount_type = $dto->discount_type;
        $coupon->discount_value = $dto->discount_value;
        $coupon->currency_id = $dto->currency_id;
        $coupon->max_redemptions = $dto->max_redemptions;
        $coupon->max_redemptions_per_tenant = $dto->max_redemptions_per_tenant;
        $coupon->minimum_amount = $dto->minimum_amount;
        $coupon->starts_at = $dto->starts_at;
        $coupon->ends_at = $dto->ends_at;
        $coupon->is_active = $dto->is_active;
    }

    private function asFloat(string $amount): float
    {
        return (float) $amount;
    }

    private function money(float|string $amount): string
    {
        return number_format(round((float) $amount, 2), 2, '.', '');
    }
}
