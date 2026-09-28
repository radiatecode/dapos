<?php

namespace DA\Inventory\DTO\StockTransfer;

use DA\Inventory\Enums\StockTransferStatus;
use Spatie\LaravelData\Data;

class StockTransferDTO extends Data
{
    public function __construct(
        public int $productVariantId,
        public int $fromStoreId,
        public int $toStoreId,
        public string $transferDate,
        public string $quantity,
        public ?string $notes,
        public StockTransferStatus $status,
    ) {}
}
