<?php

namespace DA\Inventory\Services;

use DA\Inventory\DTO\Stock\StockCostResult;
use DA\Inventory\Models\InventoryLayer;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class CostingService
{
    /**
     * @param  Collection<int, InventoryLayer>  $layers
     * @return list<array{layer: InventoryLayer, quantity: string, unit_cost: string, total_cost: string}>
     */
    public function allocate(Collection $layers, string $quantity): array
    {
        $remaining = Decimal::normalize($quantity);

        if (! Decimal::isPositive($remaining)) {
            throw ValidationException::withMessages([
                'quantity' => 'The quantity must be greater than zero.',
            ]);
        }

        $slices = [];

        foreach ($layers as $layer) {
            if (Decimal::isZero($remaining)) {
                break;
            }

            $available = Decimal::normalize($layer->remaining_qty);

            if (! Decimal::isPositive($available)) {
                continue;
            }

            $take = Decimal::compare($available, $remaining) <= 0 ? $available : $remaining;
            $unitCost = Decimal::normalize($layer->unit_cost);

            $slices[] = [
                'layer' => $layer,
                'quantity' => $take,
                'unit_cost' => $unitCost,
                'total_cost' => Decimal::mul($take, $unitCost),
            ];

            $remaining = Decimal::sub($remaining, $take);
        }

        if (Decimal::isPositive($remaining)) {
            throw ValidationException::withMessages([
                'quantity' => 'Insufficient stock for this operation.',
            ]);
        }

        return $slices;
    }

    /**
     * @param  list<array{quantity: string, total_cost: string}>  $slices
     */
    public function summarize(array $slices): StockCostResult
    {
        $quantity = '0.0000';
        $total = '0.0000';

        foreach ($slices as $slice) {
            $quantity = Decimal::add($quantity, $slice['quantity']);
            $total = Decimal::add($total, $slice['total_cost']);
        }

        return new StockCostResult(
            quantity: $quantity,
            unitCost: Decimal::div($total, $quantity),
            totalCost: $total,
        );
    }
}
