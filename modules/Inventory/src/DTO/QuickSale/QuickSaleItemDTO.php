<?php

namespace DA\Inventory\DTO\QuickSale;

use Spatie\LaravelData\Data;

class QuickSaleItemDTO extends Data
{
    public function __construct(
        public ?string $title,
        public string $unitAmount,
        public string $quantity,
    ) {}
}
