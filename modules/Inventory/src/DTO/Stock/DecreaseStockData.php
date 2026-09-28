<?php

namespace DA\Inventory\DTO\Stock;

use DA\Inventory\Enums\AllocationSourceType;
use DA\Inventory\Enums\StockMovementType;
use DA\Inventory\Enums\StockReferenceType;

final class DecreaseStockData
{
    public function __construct(
        public int $storeId,
        public int $productVariantId,
        public string $quantity,
        public AllocationSourceType $sourceType,
        public int $sourceId,
        public ?int $sourceItemId,
        public StockMovementType $movementType,
        public StockReferenceType $referenceType,
        public int $referenceId,
        public ?int $referenceItemId,
    ) {}
}
