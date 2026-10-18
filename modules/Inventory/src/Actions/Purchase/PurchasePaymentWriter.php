<?php

namespace DA\Inventory\Actions\Purchase;

use App\Models\User;
use DA\Inventory\Models\Purchase;
use DA\Inventory\Models\PurchasePayment;
use RuntimeException;

class PurchasePaymentWriter
{
    public function create(Purchase $purchase, string $amount, ?string $referenceNumber, ?string $note): PurchasePayment
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            throw new RuntimeException('Tenant context is required for purchases.');
        }

        $payment = new PurchasePayment;
        $payment->tenant_id = $purchase->tenant_id;
        $payment->purchase_id = $purchase->id;
        $payment->amount = $amount;
        $payment->reference_number = $referenceNumber;
        $payment->note = $note;
        $payment->paid_at = now();
        $payment->recorded_by = (int) $user->id;
        $payment->save();

        return $payment;
    }
}
