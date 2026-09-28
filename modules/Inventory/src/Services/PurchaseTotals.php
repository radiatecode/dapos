<?php

namespace DA\Inventory\Services;

use DA\Inventory\DTO\Purchase\PurchaseDTO;
use Illuminate\Validation\ValidationException;

class PurchaseTotals
{
    /**
     * Line total is quantity × unit cost − line discount + line tax.
     * Subtotal is the sum of quantity × unit cost.
     * Grand total is the sum of line totals − header discount + header tax + shipping.
     *
     * @return array{subtotal: string, grand_total: string, lines: list<string>}
     */
    public function calculate(PurchaseDTO $dto): array
    {
        $subtotal = '0.0000';
        $lineTotalsSum = '0.0000';
        $lines = [];

        foreach ($dto->items as $item) {
            $gross = bcmul($item->quantity, $item->unitCost, 4);
            $lineTotal = bcadd(bcsub($gross, $item->discountAmount, 4), $item->taxAmount, 4);
            $lines[] = $lineTotal;
            $subtotal = bcadd($subtotal, $gross, 4);
            $lineTotalsSum = bcadd($lineTotalsSum, $lineTotal, 4);
        }

        $grandTotal = bcadd(
            bcsub($lineTotalsSum, $dto->discountAmount, 4),
            bcadd($dto->taxAmount, $dto->shippingAmount, 4),
            4,
        );

        if (bccomp($grandTotal, '0', 4) < 0) {
            throw ValidationException::withMessages([
                'discount_amount' => 'The grand total cannot be negative.',
            ]);
        }

        return [
            'subtotal' => $subtotal,
            'grand_total' => $grandTotal,
            'lines' => $lines,
        ];
    }
}
