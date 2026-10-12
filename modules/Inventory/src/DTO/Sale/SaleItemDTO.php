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
        public float $taxAmount,
        public float $subtotal,
        public float $total,
        public ?string $variationName,
        public ?int $itemTaxType,
    ) {}
}
