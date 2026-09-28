<?php

namespace DA\Inventory\DTO\Stock;

use DA\Inventory\Enums\InventoryLayerSourceType;
use DA\Inventory\Enums\StockMovementType;
use DA\Inventory\Enums\StockReferenceType;

final class ReceiveStockData
{
    public function __construct(
        public int $storeId,
        public int $productVariantId,
        public string $quantity,
        public string $unitCost,
        public InventoryLayerSourceType $sourceType,
        public int $sourceId,
        public ?int $sourceItemId,
        public ?int $supplierId,
        public StockMovementType $movementType,
        public StockReferenceType $referenceType,
        public int $referenceId,
        public ?int $referenceItemId,
    ) {}
}
