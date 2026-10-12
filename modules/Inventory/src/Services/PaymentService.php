<?php

namespace DA\Inventory\Services;

use DA\Inventory\DTO\Sale\SalePaymentDTO;
use DA\Inventory\Enums\SalePaymentStatus;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    /**
     * @param  list<SalePaymentDTO>  $payments
     */
    public function extractPaymentLines(array $payments): array
    {
        $paid = 0;
        $due = 0;
        $lines = [];
        foreach ($payments as $index => $payment) {

            $paid += min($payment->receivedAmount, $payment->orderAmount);
            $due += $payment->dueAmount;

            $lines[] = [
                'payment_method_id' => $payment->paymentMethodId,
                'bill_amount' => $payment->orderAmount,
                'received_amount' => $payment->receivedAmount,
                'paid_amount' => $paid,
                'due_amount' => $payment->dueAmount,
                'returned_amount' => $payment->returnedAmount,
                'reference_number' => $payment->referenceNumber,
            ];
        }

        return [
            'paid_amount' => $paid,
            'due_amount' => $due,
            'lines' => $lines,
        ];
    }

    /**
     * Apply received amounts to a bill. Paid amount cannot exceed the remaining bill.
     * The extra received amount is change.
     *
     * @param  list<SalePaymentDTO>  $payments
     * @return array{
     *     paid_amount: string,
     *     due_amount: string,
     *     lines: list<array{
     *         payment_method_id: int,
     *         bill_amount: string,
     *         received_amount: string,
     *         paid_amount: string,
     *         due_amount: string,
     *         returned_amount: string,
     *         reference_number: ?string
     *     }>
     * }
     */
    public function allocate(float $billAmount, array $payments): array
    {
        $remaining = $billAmount;
        $paid = 0;
        $lines = [];
        $count = count($payments);

        foreach ($payments as $index => $payment) {
            $received = $payment->receivedAmount;

            if ($remaining < 0) {
                if ($received > 0 || $lines !== []) {
                    throw ValidationException::withMessages([
                        $this->receivedField($index, $count) => 'The sale is already fully paid by the previous payments.',
                    ]);
                }
            }

            $applied = min($received, $remaining);
            $returned = $received - $applied;
            $remaining = $remaining - $applied;
            $paid = $paid + $applied;

            $lines[] = [
                'payment_method_id' => $payment->paymentMethodId,
                'bill_amount' => $applied + $remaining,
                'received_amount' => $received,
                'paid_amount' => $applied,
                'due_amount' => $remaining,
                'returned_amount' => $returned,
                'reference_number' => $payment->referenceNumber,
            ];
        }

        return [
            'paid_amount' => $paid,
            'due_amount' => $remaining,
            'lines' => $lines,
        ];
    }

    public function paymentStatus(string $grandTotal, string $paidAmount): SalePaymentStatus
    {
        $due = Decimal::sub($grandTotal, $paidAmount);

        if (! Decimal::isPositive($due)) {
            return SalePaymentStatus::Paid;
        }

        if (Decimal::isZero($paidAmount)) {
            return SalePaymentStatus::Unpaid;
        }

        return SalePaymentStatus::Partial;
    }

    private function lesser(string $left, string $right): string
    {
        return Decimal::compare($left, $right) <= 0
            ? Decimal::normalize($left)
            : Decimal::normalize($right);
    }

    private function receivedField(int $index, int $count): string
    {
        if ($count === 1) {
            return 'received_amount';
        }

        return "payments.{$index}.received_amount";
    }
}
