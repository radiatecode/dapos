<?php

namespace DA\Inventory\Services;

use DA\Inventory\Enums\AllocationSourceType;
use DA\Inventory\Enums\InventoryLayerStatus;
use DA\Inventory\Models\InventoryAllocation;
use DA\Inventory\Models\InventoryLayer;

class StockAllocationService
{
    /**
     * @param  list<array{layer: InventoryLayer, quantity: string, unit_cost: string, total_cost: string}>  $slices
     * @return list<InventoryAllocation>
     */
    public function consume(
        array $slices,
        int $tenantId,
        int $storeId,
        int $productVariantId,
        AllocationSourceType $sourceType,
        int $sourceId,
        ?int $sourceItemId,
    ): array {
        $allocations = [];

        foreach ($slices as $slice) {
            $layer = $slice['layer'];
            $layer->remaining_qty = Decimal::sub($layer->remaining_qty, $slice['quantity']);
            $layer->status = Decimal::isZero($layer->remaining_qty)
                ? InventoryLayerStatus::Depleted
                : InventoryLayerStatus::Open;
            $layer->save();

            $allocation = new InventoryAllocation;
            $allocation->tenant_id = $tenantId;
            $allocation->store_id = $storeId;
            $allocation->product_variant_id = $productVariantId;
            $allocation->inventory_layer_id = $layer->id;
            $allocation->source_type = $sourceType;
            $allocation->source_id = $sourceId;
            $allocation->source_item_id = $sourceItemId;
            $allocation->quantity = $slice['quantity'];
            $allocation->unit_cost = $slice['unit_cost'];
            $allocation->total_cost = $slice['total_cost'];
            $allocation->save();

            $allocations[] = $allocation;
        }

        return $allocations;
    }
}
