<?php

namespace DA\Inventory\DTO\StockAdjustment;

use DA\Inventory\Enums\StockAdjustmentStatus;
use DA\Inventory\Enums\StockAdjustmentType;
use Spatie\LaravelData\Data;

class StockAdjustmentDTO extends Data
{
    public function __construct(
        public int $storeId,
        public int $productVariantId,
        public string $adjustmentDate,
        public StockAdjustmentType $adjustmentType,
        public string $reason,
        public string $adjustedQuantity,
        public ?string $unitCost,
        public ?string $notes,
        public StockAdjustmentStatus $status,
    ) {}
}
