<?php

use DA\Admin\DTO\GenerateInvoiceDTO;
use DA\Admin\DTO\RecordPaymentDTO;
use DA\Admin\Enums\PaymentMethod;
use DA\Admin\Enums\PaymentStatus;
use DA\Admin\Enums\SubscriptionItemType;
use DA\Admin\Exceptions\BillingException;
use DA\Admin\Models\Plan;
use DA\Admin\Models\Subscription;
use DA\Admin\Services\InvoiceService;
use DA\Admin\Services\PaymentService;

function payableInvoice()
{
    $plan = Plan::factory()->create(['price' => '19.00']);
    $subscription = Subscription::factory()->create(['plan_id' => $plan->id]);
    $subscription->items()->create([
        'item_type' => SubscriptionItemType::Plan,
        'reference_id' => $plan->id,
        'quantity' => 1,
        'unit_price' => $plan->price,
    ]);

    return app(InvoiceService::class)->generate(new GenerateInvoiceDTO(
        subscription_id: $subscription->id,
    ));
}

it('records a payment through the processor interface', function () {
    $invoice = payableInvoice();

    $paid = app(PaymentService::class)->recordSuccess($invoice, new RecordPaymentDTO(
        payment_method: PaymentMethod::BankTransfer,
        transaction_id: 'bank-123',
    ));

    expect($paid->isPaid())->toBeTrue()
        ->and($paid->payments->first()->transaction_id)->toBe('bank-123')
        ->and($paid->payments->first()->payment_method)->toBe(PaymentMethod::BankTransfer)
        ->and($paid->payments->first()->status)->toBe(PaymentStatus::Succeeded);

    $this->assertDatabaseHas('subscription_payments', [
        'invoice_id' => $invoice->id,
        'transaction_id' => 'bank-123',
        'status' => PaymentStatus::Succeeded->value,
    ]);
});

it('rejects recording a second successful payment on a paid invoice', function () {
    $invoice = payableInvoice();
    $payments = app(PaymentService::class);
    $paid = $payments->recordSuccess($invoice, new RecordPaymentDTO);

    expect(fn () => $payments->recordSuccess($paid, new RecordPaymentDTO))
        ->toThrow(BillingException::class, 'This invoice is already paid.');
});
