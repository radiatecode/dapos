<?php

use DA\Admin\DTO\GenerateInvoiceDTO;
use DA\Admin\Enums\AdminPermission;
use DA\Admin\Enums\InvoiceStatus;
use DA\Admin\Enums\PaymentMethod;
use DA\Admin\Enums\PaymentStatus;
use DA\Admin\Enums\SubscriptionItemType;
use DA\Admin\Models\Plan;
use DA\Admin\Models\Subscription;
use DA\Admin\Services\InvoiceService;

function httpBilledSubscription(): Subscription
{
    $plan = Plan::factory()->create(['name' => 'Starter', 'price' => '19.00']);
    $subscription = Subscription::factory()->create(['plan_id' => $plan->id]);
    $subscription->items()->create([
        'item_type' => SubscriptionItemType::Plan,
        'reference_id' => $plan->id,
        'quantity' => 1,
        'unit_price' => $plan->price,
    ]);

    return $subscription->fresh(['tenant', 'plan']);
}

describe('index', function () {
    it('redirects guests from the invoice list to login', function () {
        $this->get(route('admin.invoices.index'))
            ->assertRedirect(route('admin.login'));
    });

    it('forbids an admin without invoices.view', function () {
        actingAsPlatformAdmin([AdminPermission::PlansView]);

        $this->get(route('admin.invoices.index'))->assertForbidden();
    });

    it('renders the invoice datatable for a platform admin', function () {
        actingAsPlatformAdmin();

        $this->get(route('admin.invoices.index'))
            ->assertOk()
            ->assertSee('invoices-table', false)
            ->assertSee('Generate Invoice');
    });
});

describe('create and store', function () {
    it('forbids invoice generation without invoices.create', function () {
        actingAsPlatformAdmin([AdminPermission::InvoicesView]);
        $subscription = httpBilledSubscription();

        $this->get(route('admin.invoices.create'))->assertForbidden();
        $this->post(route('admin.invoices.store'), ['subscription_id' => $subscription->id])
            ->assertForbidden();
    });

    it('generates an invoice from the create form', function () {
        actingAsPlatformAdmin();
        $subscription = httpBilledSubscription();

        $this->post(route('admin.invoices.store'), [
            'subscription_id' => $subscription->id,
        ])->assertRedirect();

        $this->assertDatabaseHas('invoices', [
            'subscription_id' => $subscription->id,
            'tenant_id' => $subscription->tenant_id,
            'status' => InvoiceStatus::Open->value,
            'total_amount' => '19.00',
        ]);
    });
});

describe('show and payments', function () {
    it('renders invoice details for an authorized admin', function () {
        actingAsPlatformAdmin();
        $subscription = httpBilledSubscription();
        $invoice = app(InvoiceService::class)->generate(new GenerateInvoiceDTO(
            subscription_id: $subscription->id,
        ));

        $this->get(route('admin.invoices.show', $invoice))
            ->assertOk()
            ->assertSee($invoice->invoice_number)
            ->assertSee($subscription->tenant->name)
            ->assertSee('Mark paid');
    });

    it('marks an invoice paid and records the payment', function () {
        actingAsPlatformAdmin();
        $subscription = httpBilledSubscription();
        $invoice = app(InvoiceService::class)->generate(new GenerateInvoiceDTO(
            subscription_id: $subscription->id,
        ));

        $this->post(route('admin.invoices.mark-paid', $invoice), recordPaymentPayload([
            'transaction_id' => 'manual-99',
        ]))->assertRedirect(route('admin.invoices.show', $invoice));

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => InvoiceStatus::Paid->value,
        ]);
        $this->assertDatabaseHas('subscription_payments', [
            'invoice_id' => $invoice->id,
            'transaction_id' => 'manual-99',
            'status' => PaymentStatus::Succeeded->value,
        ]);
    });

    it('marks an open invoice failed', function () {
        actingAsPlatformAdmin();
        $subscription = httpBilledSubscription();
        $invoice = app(InvoiceService::class)->generate(new GenerateInvoiceDTO(
            subscription_id: $subscription->id,
        ));

        $this->post(route('admin.invoices.mark-failed', $invoice), [
            'payment_method' => PaymentMethod::Manual->value,
        ])->assertRedirect(route('admin.invoices.show', $invoice));

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => InvoiceStatus::Failed->value,
        ]);
    });

    it('forbids payment actions without invoices.manage', function () {
        actingAsPlatformAdmin([AdminPermission::InvoicesView]);
        $subscription = httpBilledSubscription();
        $invoice = app(InvoiceService::class)->generate(new GenerateInvoiceDTO(
            subscription_id: $subscription->id,
        ));

        $this->post(route('admin.invoices.mark-paid', $invoice), recordPaymentPayload())
            ->assertForbidden();
    });
});
