<?php

namespace DA\Inventory\DTO\PurchaseReturn;

use Spatie\LaravelData\Data;

class PurchaseReturnItemDTO extends Data
{
    public function __construct(
        public ?int $id,
        public int $purchaseOrderItemId,
        public int $productVariantId,
        public string $quantity,
        public string $unitCost,
        public string $discountAmount,
        public string $taxAmount,
    ) {}
}
