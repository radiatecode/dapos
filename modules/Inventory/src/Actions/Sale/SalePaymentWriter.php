<?php

namespace DA\Inventory\Actions\Sale;

use App\Models\User;
use DA\Inventory\Models\Payment;
use DA\Inventory\Models\Sale;
use RuntimeException;

class SalePaymentWriter
{
    /**
     * @param  list<array{
     *     payment_method: int,
     *     bill_amount: string,
     *     received_amount: string,
     *     paid_amount: string,
     *     due_amount: string,
     *     returned_amount: string,
     *     reference_number: ?string
     * }>  $lines
     */
    public function create(Sale $sale, array $lines): void
    {
        $receiverId = $this->receiverId();

        foreach ($lines as $line) {
            $payment = new Payment;
            $payment->tenant_id = $sale->tenant_id;
            $payment->sale_id = $sale->id;
            $payment->customer_id = $sale->customer_id;
            $payment->payment_method = $line['payment_method_id'];
            $payment->bill_amount = $line['bill_amount'];
            $payment->received_amount = $line['received_amount'];
            $payment->paid_amount = $line['paid_amount'];
            $payment->due_amount = $line['due_amount'];
            $payment->returned_amount = $line['returned_amount'];
            $payment->reference_number = $line['reference_number'];
            $payment->paid_at = now();
            $payment->received_by = $receiverId;
            $payment->save();
        }
    }

    private function receiverId(): int
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            throw new RuntimeException('Tenant context is required for sales.');
        }

        return (int) $user->id;
    }
}
