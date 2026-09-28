<?php

namespace DA\Inventory\DTO\Stock;

final class TransferStockData
{
    public function __construct(
        public int $fromStoreId,
        public int $toStoreId,
        public int $productVariantId,
        public string $quantity,
        public int $transferId,
    ) {}
}
