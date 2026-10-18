<?php

namespace DA\Inventory\Services;

use DA\Inventory\DTO\Purchase\PurchaseDTO;
use DA\Inventory\Enums\PurchasePaymentStatus;
use Illuminate\Validation\ValidationException;

class PurchaseTotals
{
    /**
     * Line total is quantity × unit cost − line discount, plus line tax when the tax is exclusive.
     * Inclusive line tax is stored on the line and left out of the line total.
     * Subtotal is the sum of quantity × unit cost.
     * Grand total is the sum of line totals − header discount + header tax.
     * Payment status follows the paid amount: zero is unpaid, less than the grand total is partial, and equal is paid.
     *
     * @return array{
     *     subtotal: string,
     *     grand_total: string,
     *     paid_amount: string,
     *     due_amount: string,
     *     payment_status: PurchasePaymentStatus,
     *     lines: list<string>
     * }
     */
    public function calculate(PurchaseDTO $dto): array
    {
        $subtotal = '0.0000';
        $lineTotalsSum = '0.0000';
        $lines = [];

        foreach ($dto->items as $item) {
            $gross = bcmul($item->quantity, $item->unitCost, 4);
            $net = bcsub($gross, $item->discountAmount, 4);
            $lineTotal = $item->taxIncluded ? $net : bcadd($net, $item->taxAmount, 4);
            $lines[] = $lineTotal;
            $subtotal = bcadd($subtotal, $gross, 4);
            $lineTotalsSum = bcadd($lineTotalsSum, $lineTotal, 4);
        }

        $grandTotal = bcadd(bcsub($lineTotalsSum, $dto->discountAmount, 4), $dto->taxAmount, 4);

        if (bccomp($grandTotal, '0', 4) < 0) {
            throw ValidationException::withMessages([
                'discount_amount' => 'The grand total cannot be negative.',
            ]);
        }

        $settled = $this->settle($grandTotal, $dto->paidAmount);

        return [
            'subtotal' => $subtotal,
            'grand_total' => $grandTotal,
            'paid_amount' => $settled['paid_amount'],
            'due_amount' => $settled['due_amount'],
            'payment_status' => $settled['payment_status'],
            'lines' => $lines,
        ];
    }

    /**
     * @return array{paid_amount: string, due_amount: string, payment_status: PurchasePaymentStatus}
     */
    public function settle(string $grandTotal, string $paidAmount): array
    {
        if (Decimal::compare($paidAmount, $grandTotal) === 1) {
            throw ValidationException::withMessages([
                'paid_amount' => 'The paid amount cannot exceed the grand total.',
            ]);
        }

        return [
            'paid_amount' => Decimal::normalize($paidAmount),
            'due_amount' => Decimal::sub($grandTotal, $paidAmount),
            'payment_status' => PurchasePaymentStatus::fromAmounts($grandTotal, $paidAmount),
        ];
    }
}
