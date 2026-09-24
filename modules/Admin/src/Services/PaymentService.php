<?php

namespace DA\Admin\Services;

use DA\Admin\DTO\RecordPaymentDTO;
use DA\Admin\Enums\InvoiceStatus;
use DA\Admin\Enums\PaymentStatus;
use DA\Admin\Exceptions\BillingException;
use DA\Admin\Models\Invoice;
use DA\Admin\Models\SubscriptionPayment;
use DA\Admin\Services\Billing\PaymentProcessor;
use DA\Admin\Services\Billing\PaymentRequest;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function __construct(private PaymentProcessor $processor) {}

    public function recordSuccess(Invoice $invoice, RecordPaymentDTO $dto): Invoice
    {
        return $this->record($invoice, $dto, PaymentStatus::Succeeded);
    }

    public function recordFailure(Invoice $invoice, RecordPaymentDTO $dto): Invoice
    {
        return $this->record($invoice, $dto, PaymentStatus::Failed);
    }

    private function record(Invoice $invoice, RecordPaymentDTO $dto, PaymentStatus $intended): Invoice
    {
        return $this->transact(function () use ($invoice, $dto, $intended): Invoice {
            $locked = Invoice::query()
                ->whereKey($invoice->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($intended === PaymentStatus::Succeeded && $locked->isPaid()) {
                throw new BillingException('This invoice is already paid.');
            }

            if ($intended === PaymentStatus::Failed && ! $locked->isOpen()) {
                throw new BillingException('Only an open invoice can be marked as failed.');
            }

            if ($intended === PaymentStatus::Succeeded && ! $locked->isOpen() && ! $locked->isFailed()) {
                throw new BillingException('This invoice cannot be marked as paid.');
            }

            $outcome = $this->processor->settle(new PaymentRequest(
                invoice: $locked,
                intendedStatus: $intended,
                method: $dto->payment_method,
                amount: $locked->total_amount,
                transactionId: $dto->transaction_id,
            ));

            $payment = new SubscriptionPayment;
            $payment->tenant_id = $locked->tenant_id;
            $payment->invoice_id = $locked->id;
            $payment->amount = $locked->total_amount;
            $payment->currency_id = $locked->currency_id;
            $payment->payment_method = $dto->payment_method;
            $payment->transaction_id = $outcome->transactionId;
            $payment->status = $outcome->status;
            $payment->paid_at = $outcome->paidAt;
            $payment->save();

            if ($outcome->status === PaymentStatus::Succeeded) {
                $locked->status = InvoiceStatus::Paid;
                $locked->paid_at = $outcome->paidAt;
            }

            if ($outcome->status === PaymentStatus::Failed) {
                $locked->status = InvoiceStatus::Failed;
                $locked->paid_at = null;
            }

            $locked->save();

            return $locked->fresh(['payments', 'items', 'currency', 'tenant', 'subscription.plan']);
        });
    }

    /**
     * @template TReturn
     *
     * @param  callable(): TReturn  $callback
     * @return TReturn
     */
    private function transact(callable $callback): mixed
    {
        if (DB::transactionLevel() > 0) {
            return $callback();
        }

        return DB::transaction($callback);
    }
}
