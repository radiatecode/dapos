<?php

namespace DA\Inventory\Actions\Purchase;

use DA\Inventory\DTO\Stock\ReceiveStockData;
use DA\Inventory\Enums\InventoryLayerSourceType;
use DA\Inventory\Enums\PurchaseStatus;
use DA\Inventory\Enums\StockMovementType;
use DA\Inventory\Enums\StockReferenceType;
use DA\Inventory\Models\Purchase;
use DA\Inventory\Services\Decimal;
use DA\Inventory\Services\InventoryService;

class ReceivePurchaseStock
{
    public function __construct(private InventoryService $inventory) {}

    public function handle(Purchase $purchase): void
    {
        if ($purchase->status !== PurchaseStatus::Received) {
            return;
        }

        $purchase->load('items.variant.product');

        foreach ($purchase->items as $item) {
            if ($item->variant === null || $item->variant->product === null || ! $item->variant->product->track_inventory) {
                continue;
            }

            if (! Decimal::isPositive($item->quantity)) {
                continue;
            }

            $this->inventory->receive(new ReceiveStockData(
                storeId: $purchase->store_id,
                productVariantId: $item->product_variant_id,
                quantity: Decimal::normalize($item->quantity),
                unitCost: Decimal::normalize($item->unit_cost),
                sourceType: InventoryLayerSourceType::GoodsReceipt,
                sourceId: $purchase->id,
                sourceItemId: $item->id,
                supplierId: $purchase->supplier_id,
                movementType: StockMovementType::GoodsReceipt,
                referenceType: StockReferenceType::Purchase,
                referenceId: $purchase->id,
                referenceItemId: $item->id,
            ));
        }
    }
}
