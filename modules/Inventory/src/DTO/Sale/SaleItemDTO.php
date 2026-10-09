<?php

namespace DA\Inventory\DTO\Sale;

use Spatie\LaravelData\Data;

class SaleItemDTO extends Data
{
    public function __construct(
        public int $productVariantId,
        public int $productId,
        public string $productName,
        public float $quantity,
        public float $unitPrice,
        public array $taxes,
        public ?string $variationName,
        public ?int $itemTaxType,
    ) {}
}
