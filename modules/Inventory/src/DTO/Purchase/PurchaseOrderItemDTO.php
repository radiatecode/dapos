<?php

namespace DA\Inventory\DTO\Purchase;

use Spatie\LaravelData\Data;

class PurchaseOrderItemDTO extends Data
{
    public function __construct(
        public ?int $id,
        public int $productVariantId,
        public string $quantity,
        public string $unitCost,
        public string $discountAmount,
        public string $taxAmount,
    ) {}
}
