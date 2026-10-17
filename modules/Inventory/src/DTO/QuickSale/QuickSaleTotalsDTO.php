<?php

namespace DA\Inventory\DTO\QuickSale;

use Spatie\LaravelData\Data;

class QuickSaleTotalsDTO extends Data
{
    /**
     * @param  list<array{title: string|null, unit_amount: string, quantity: string, amount: string}>  $lines
     */
    public function __construct(
        public array $lines,
        public int $totalItems,
        public string $grandTotal,
        public string $changeAmount,
    ) {}
}
