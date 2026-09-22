<?php

use DA\Admin\DTO\GenerateInvoiceDTO;
use DA\Admin\DTO\RecordPaymentDTO;
use DA\Admin\Enums\InvoiceItemType;
use DA\Admin\Enums\InvoiceStatus;
use DA\Admin\Enums\PaymentMethod;
use DA\Admin\Enums\PaymentStatus;
use DA\Admin\Enums\SubscriptionItemType;
use DA\Admin\Exceptions\BillingException;
use DA\Admin\Models\Addon;
use DA\Admin\Models\Coupon;
use DA\Admin\Models\Plan;
use DA\Admin\Models\Subscription;
use DA\Admin\Services\InvoiceService;

function billedSubscription(array $planAttributes = [], array $addons = []): Subscription
{
    $plan = Plan::factory()->create([
        'price' => '29.00',
        ...$planAttributes,
    ]);

    $subscription = Subscription::factory()->create([
        'plan_id' => $plan->id,
    ]);

    $subscription->items()->create([
        'item_type' => SubscriptionItemType::Plan,
        'reference_id' => $plan->id,
        'quantity' => 1,
        'unit_price' => $plan->price,
    ]);

    foreach ($addons as $addon) {
        $subscription->items()->create([
            'item_type' => SubscriptionItemType::Addon,
            'reference_id' => $addon->id,
            'quantity' => 1,
            'unit_price' => $addon->price,
        ]);
    }

    return $subscription->fresh(['plan.currency', 'items', 'tenant']);
}

it('generates an open invoice from subscription items', function () {
    $addon = Addon::factory()->create(['price' => '9.00']);
    $subscription = billedSubscription([], [$addon]);

    $invoice = app(InvoiceService::class)->generate(new GenerateInvoiceDTO(
        subscription_id: $subscription->id,
    ));

    expect($invoice->status)->toBe(InvoiceStatus::Open)
        ->and($invoice->tenant_id)->toBe($subscription->tenant_id)
        ->and($invoice->subtotal)->toBe('38.00')
        ->and($invoice->discount_amount)->toBe('0.00')
        ->and($invoice->tax_amount)->toBe('0.00')
        ->and($invoice->total_amount)->toBe('38.00')
        ->and($invoice->invoice_number)->toStartWith('INV-')
        ->and($invoice->items)->toHaveCount(2);

    $this->assertDatabaseHas('invoice_items', [
        'invoice_id' => $invoice->id,
        'item_type' => InvoiceItemType::Plan->value,
        'total_amount' => '29.00',
    ]);
    $this->assertDatabaseHas('invoice_items', [
        'invoice_id' => $invoice->id,
        'item_type' => InvoiceItemType::Addon->value,
        'total_amount' => '9.00',
    ]);
});

it('applies a percentage coupon to invoice totals and records a redemption', function () {
    $subscription = billedSubscription();
    Coupon::factory()->percentage('10.00')->create(['code' => 'SAVE10']);

    $invoice = app(InvoiceService::class)->generate(new GenerateInvoiceDTO(
        subscription_id: $subscription->id,
        coupon_code: 'save10',
    ));

    expect($invoice->subtotal)->toBe('29.00')
        ->and($invoice->discount_amount)->toBe('2.90')
        ->and($invoice->total_amount)->toBe('26.10');

    $this->assertDatabaseHas('coupon_redemptions', [
        'invoice_id' => $invoice->id,
        'tenant_id' => $subscription->tenant_id,
        'discount_amount' => '2.90',
    ]);
});

it('rejects a second open invoice for the same billing period', function () {
    $subscription = billedSubscription();
    $service = app(InvoiceService::class);

    $service->generate(new GenerateInvoiceDTO(subscription_id: $subscription->id));

    expect(fn () => $service->generate(new GenerateInvoiceDTO(subscription_id: $subscription->id)))
        ->toThrow(BillingException::class, 'An open invoice already exists for this billing period.');
});

it('records a successful payment and marks the invoice paid', function () {
    $subscription = billedSubscription();
    $service = app(InvoiceService::class);
    $invoice = $service->generate(new GenerateInvoiceDTO(subscription_id: $subscription->id));

    $paid = $service->markPaid($invoice, new RecordPaymentDTO(payment_method: PaymentMethod::Manual));

    expect($paid->status)->toBe(InvoiceStatus::Paid)
        ->and($paid->paid_at)->not->toBeNull()
        ->and($paid->payments)->toHaveCount(1)
        ->and($paid->payments->first()->status)->toBe(PaymentStatus::Succeeded)
        ->and($paid->payments->first()->amount)->toBe('29.00');
});

it('records a failed payment and keeps the attempt in history', function () {
    $subscription = billedSubscription();
    $service = app(InvoiceService::class);
    $invoice = $service->generate(new GenerateInvoiceDTO(subscription_id: $subscription->id));

    $failed = $service->markFailed($invoice, new RecordPaymentDTO);

    expect($failed->status)->toBe(InvoiceStatus::Failed)
        ->and($failed->paid_at)->toBeNull()
        ->and($failed->payments->first()->status)->toBe(PaymentStatus::Failed);

    $paid = $service->markPaid($failed, new RecordPaymentDTO);

    expect($paid->status)->toBe(InvoiceStatus::Paid)
        ->and($paid->payments)->toHaveCount(2);
});

it('returns tenant billing history without another tenant invoices', function () {
    $first = billedSubscription();
    $second = billedSubscription();
    $service = app(InvoiceService::class);

    $firstInvoice = $service->generate(new GenerateInvoiceDTO(subscription_id: $first->id));
    $service->generate(new GenerateInvoiceDTO(subscription_id: $second->id));

    $history = $service->historyForTenant($first->tenant_id);

    expect($history)->toHaveCount(1)
        ->and($history->first()->id)->toBe($firstInvoice->id);
});

it('returns subscription billing history for generated invoices', function () {
    $subscription = billedSubscription();
    $service = app(InvoiceService::class);
    $invoice = $service->generate(new GenerateInvoiceDTO(subscription_id: $subscription->id));

    $history = $service->historyForSubscription($subscription->id);

    expect($history)->toHaveCount(1)
        ->and($history->first()->invoice_number)->toBe($invoice->invoice_number);
});
