<?php

namespace DA\Inventory\Services;

use DA\Inventory\Enums\StockDirection;
use DA\Inventory\Enums\StockMovementType;
use DA\Inventory\Enums\StockReferenceType;
use DA\Inventory\Models\StockMovement;

class StockMovementService
{
    public function post(
        int $tenantId,
        int $storeId,
        int $productVariantId,
        StockMovementType $movementType,
        string $quantity,
        string $unitCost,
        StockReferenceType $referenceType,
        int $referenceId,
        ?int $referenceItemId,
        StockDirection $direction,
        int $inventoryLayerId,
    ): StockMovement {
        $existing = StockMovement::queries()->findPosted(
            $tenantId,
            $storeId,
            $referenceType,
            $referenceId,
            $referenceItemId,
            $direction,
            $inventoryLayerId,
        );

        if ($existing instanceof StockMovement) {
            return $existing;
        }

        $movement = new StockMovement;
        $movement->tenant_id = $tenantId;
        $movement->store_id = $storeId;
        $movement->product_variant_id = $productVariantId;
        $movement->movement_type = $movementType;
        $movement->quantity = Decimal::normalize($quantity);
        $movement->unit_cost = Decimal::normalize($unitCost);
        $movement->reference_type = $referenceType;
        $movement->reference_id = $referenceId;
        $movement->reference_item_id = $referenceItemId;
        $movement->inventory_layer_id = $inventoryLayerId;
        $movement->direction = $direction;
        $movement->occurred_at = now();
        $movement->created_by = auth()->id();
        $movement->save();

        return $movement;
    }
}
