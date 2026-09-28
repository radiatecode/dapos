<?php

namespace DA\Inventory\DTO\Stock;

final class StockCostResult
{
    public function __construct(
        public string $quantity,
        public string $unitCost,
        public string $totalCost,
    ) {}
}
