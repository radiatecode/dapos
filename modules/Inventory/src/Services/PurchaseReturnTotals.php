<?php

namespace DA\Inventory\Services;

use DA\Inventory\DTO\PurchaseReturn\PurchaseReturnDTO;
use Illuminate\Validation\ValidationException;

class PurchaseReturnTotals
{
    /**
     * Line subtotal is quantity × unit cost.
     * Line total is that subtotal − line discount + line tax.
     * Header subtotal is the sum of line subtotals.
     * Grand total is the sum of line totals − header discount + header tax.
     *
     * @return array{
     *     subtotal: string,
     *     grand_total: string,
     *     total_return_qty: string,
     *     lines: list<array{subtotal: string, total_amount: string}>
     * }
     */
    public function calculate(PurchaseReturnDTO $dto): array
    {
        $subtotal = '0.0000';
        $lineTotalsSum = '0.0000';
        $totalReturnQty = '0.0000';
        $lines = [];

        foreach ($dto->items as $item) {
            $gross = bcmul($item->quantity, $item->unitCost, 4);
            $lineTotal = bcadd(bcsub($gross, $item->discountAmount, 4), $item->taxAmount, 4);
            $lines[] = [
                'subtotal' => $gross,
                'total_amount' => $lineTotal,
            ];
            $subtotal = bcadd($subtotal, $gross, 4);
            $lineTotalsSum = bcadd($lineTotalsSum, $lineTotal, 4);
            $totalReturnQty = bcadd($totalReturnQty, $item->quantity, 4);
        }

        $grandTotal = bcadd(bcsub($lineTotalsSum, $dto->discountAmount, 4), $dto->taxAmount, 4);

        if (bccomp($grandTotal, '0', 4) < 0) {
            throw ValidationException::withMessages([
                'discount_amount' => 'The grand total cannot be negative.',
            ]);
        }

        return [
            'subtotal' => $subtotal,
            'grand_total' => $grandTotal,
            'total_return_qty' => $totalReturnQty,
            'lines' => $lines,
        ];
    }
}
