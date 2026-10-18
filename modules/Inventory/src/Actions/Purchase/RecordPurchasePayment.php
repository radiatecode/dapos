<?php

namespace DA\Inventory\Actions\Purchase;

use DA\Inventory\DTO\Purchase\PurchasePaymentDTO;
use DA\Inventory\Enums\PurchasePaymentStatus;
use DA\Inventory\Models\Purchase;
use DA\Inventory\Services\Decimal;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecordPurchasePayment
{
    public function __construct(private PurchasePaymentWriter $payments) {}

    public function handle(int $purchaseId, PurchasePaymentDTO $payment): Purchase
    {
        return DB::transaction(function () use ($purchaseId, $payment): Purchase {
            $purchase = Purchase::queries()->lockForTenant($purchaseId);

            if (! $purchase->authorizeAction($purchase, 'update')) {
                throw ValidationException::withMessages([
                    'purchase' => 'You are not authorized to update this purchase.',
                ]);
            }

            if (! Decimal::isPositive($purchase->due_amount)) {
                throw ValidationException::withMessages([
                    'amount' => 'This purchase has no balance due.',
                ]);
            }

            if (Decimal::compare($payment->amount, $purchase->due_amount) === 1) {
                throw ValidationException::withMessages([
                    'amount' => 'The amount cannot exceed the balance due.',
                ]);
            }

            $this->payments->create(
                $purchase,
                Decimal::normalize($payment->amount),
                $payment->referenceNumber,
                $payment->note,
            );

            $paid = Decimal::add($purchase->paid_amount, $payment->amount);
            $purchase->paid_amount = $paid;
            $purchase->due_amount = Decimal::sub($purchase->grand_total, $paid);
            $purchase->payment_status = PurchasePaymentStatus::fromAmounts(
                Decimal::normalize($purchase->grand_total),
                $paid,
            );
            $purchase->save();

            return Purchase::queries()->findForDetail($purchase->id);
        });
    }
}
