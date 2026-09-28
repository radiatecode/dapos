<?php

namespace DA\Inventory\Actions\Product;

use DA\Inventory\DTO\Stock\ReceiveStockData;
use DA\Inventory\Enums\InventoryLayerSourceType;
use DA\Inventory\Enums\StockMovementType;
use DA\Inventory\Enums\StockReferenceType;
use DA\Inventory\Models\Product;
use DA\Inventory\Services\Decimal;
use DA\Inventory\Services\InventoryService;

class PostOpeningStock
{
    public function __construct(private InventoryService $inventory) {}

    public function handle(Product $product, ?int $storeId): void
    {
        if (! $product->track_inventory || $storeId === null) {
            return;
        }

        $product->load('variants');

        foreach ($product->variants as $variant) {
            if (! Decimal::isPositive($variant->quantity) || $variant->cost_price === null) {
                continue;
            }

            $this->inventory->receive(new ReceiveStockData(
                storeId: $storeId,
                productVariantId: $variant->id,
                quantity: Decimal::normalize($variant->quantity),
                unitCost: Decimal::normalize($variant->cost_price),
                sourceType: InventoryLayerSourceType::OpeningStock,
                sourceId: $product->id,
                sourceItemId: $variant->id,
                supplierId: null,
                movementType: StockMovementType::OpeningStock,
                referenceType: StockReferenceType::Product,
                referenceId: $product->id,
                referenceItemId: $variant->id,
            ));
        }
    }
}
