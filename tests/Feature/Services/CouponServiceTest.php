<?php

use DA\Admin\DTO\GenerateInvoiceDTO;
use DA\Admin\Enums\SubscriptionItemType;
use DA\Admin\Exceptions\BillingException;
use DA\Admin\Models\Coupon;
use DA\Admin\Models\Plan;
use DA\Admin\Models\Subscription;
use DA\Admin\Services\CouponService;
use DA\Admin\Services\InvoiceService;

function couponSubscription(): Subscription
{
    $plan = Plan::factory()->create(['price' => '40.00']);
    $subscription = Subscription::factory()->create(['plan_id' => $plan->id]);
    $subscription->items()->create([
        'item_type' => SubscriptionItemType::Plan,
        'reference_id' => $plan->id,
        'quantity' => 1,
        'unit_price' => $plan->price,
    ]);

    return $subscription->fresh(['plan', 'tenant']);
}

it('accepts an active coupon for the invoice subtotal', function () {
    $subscription = couponSubscription();
    Coupon::factory()->percentage('25.00')->create(['code' => 'SPRING']);

    $coupon = app(CouponService::class)->validate(
        'spring',
        $subscription->tenant,
        $subscription,
        '40.00',
    );

    expect($coupon->code)->toBe('SPRING')
        ->and(app(CouponService::class)->discountFor($coupon, '40.00'))->toBe('10.00');
});

it('rejects an inactive or expired coupon', function () {
    $subscription = couponSubscription();
    Coupon::factory()->inactive()->create(['code' => 'DEAD']);
    Coupon::factory()->expired()->create(['code' => 'OLD']);

    expect(fn () => app(CouponService::class)->validate('DEAD', $subscription->tenant, $subscription, '40.00'))
        ->toThrow(BillingException::class, 'This coupon is not active.');

    expect(fn () => app(CouponService::class)->validate('OLD', $subscription->tenant, $subscription, '40.00'))
        ->toThrow(BillingException::class, 'This coupon has expired.');
});

it('rejects a coupon that has reached its global usage limit', function () {
    $first = couponSubscription();
    $second = couponSubscription();
    Coupon::factory()->percentage('10.00')->create([
        'code' => 'ONCE',
        'max_redemptions' => 1,
        'max_redemptions_per_tenant' => null,
    ]);

    app(InvoiceService::class)->generate(new GenerateInvoiceDTO(
        subscription_id: $first->id,
        coupon_code: 'ONCE',
    ));

    expect(fn () => app(CouponService::class)->validate('ONCE', $second->tenant, $second, '40.00'))
        ->toThrow(BillingException::class, 'This coupon has reached its usage limit.');
});

it('rejects a coupon when the tenant has already used it', function () {
    $subscription = couponSubscription();
    Coupon::factory()->percentage('10.00')->create([
        'code' => 'ONCEEACH',
        'max_redemptions_per_tenant' => 1,
    ]);

    app(InvoiceService::class)->generate(new GenerateInvoiceDTO(
        subscription_id: $subscription->id,
        coupon_code: 'ONCEEACH',
    ));

    $nextStart = $subscription->current_period_end->copy()->addSecond();
    $subscription->update([
        'current_period_start' => $nextStart,
        'current_period_end' => $nextStart->copy()->addMonth(),
    ]);

    expect(fn () => app(CouponService::class)->validate(
        'ONCEEACH',
        $subscription->tenant,
        $subscription->fresh(['plan', 'tenant']),
        '40.00',
    ))->toThrow(BillingException::class, 'This tenant has already used this coupon the allowed number of times.');
});

it('rejects a coupon when the invoice subtotal is below the minimum', function () {
    $subscription = couponSubscription();
    Coupon::factory()->percentage('10.00')->create([
        'code' => 'BIG',
        'minimum_amount' => '50.00',
    ]);

    expect(fn () => app(CouponService::class)->validate('BIG', $subscription->tenant, $subscription, '40.00'))
        ->toThrow(BillingException::class, 'This coupon requires a higher invoice subtotal.');
});
