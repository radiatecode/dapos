<?php

namespace DA\Inventory\Services;

use DA\Inventory\DTO\QuickSale\QuickSaleDTO;
use DA\Inventory\DTO\QuickSale\QuickSaleItemDTO;
use DA\Inventory\DTO\QuickSale\QuickSaleTotalsDTO;
use Illuminate\Validation\ValidationException;

class QuickSaleService
{
    /**
     * Line amounts and the grand total are always recomputed here; totals sent by the client are never trusted.
     *
     * @throws ValidationException
     */
    public function calculate(QuickSaleDTO $dto): QuickSaleTotalsDTO
    {
        $grandTotal = '0.0000';
        $lines = [];

        foreach ($dto->items as $item) {
            $amount = $this->lineAmount($item);
            $grandTotal = Decimal::add($grandTotal, $amount);

            $lines[] = [
                'title' => $item->title,
                'unit_amount' => Decimal::normalize($item->unitAmount),
                'quantity' => Decimal::normalize($item->quantity),
                'amount' => $amount,
            ];
        }

        if (! Decimal::isPositive($grandTotal)) {
            throw ValidationException::withMessages([
                'items' => 'The quick sale total must be greater than zero.',
            ]);
        }

        if (Decimal::compare($dto->receivedAmount, $grandTotal) < 0) {
            throw ValidationException::withMessages([
                'received_amount' => 'The received amount must cover the quick sale total.',
            ]);
        }

        return new QuickSaleTotalsDTO(
            lines: $lines,
            totalItems: count($lines),
            grandTotal: $grandTotal,
            changeAmount: Decimal::sub($dto->receivedAmount, $grandTotal),
        );
    }

    private function lineAmount(QuickSaleItemDTO $item): string
    {
        return Decimal::mul($item->unitAmount, $item->quantity);
    }
}
