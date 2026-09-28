<?php

namespace DA\Inventory\Actions\StockAdjustment;

use DA\Inventory\Actions\Inventory\EnsureTrackedVariant;
use DA\Inventory\DTO\Stock\DecreaseStockData;
use DA\Inventory\DTO\Stock\ReceiveStockData;
use DA\Inventory\Enums\AllocationSourceType;
use DA\Inventory\Enums\InventoryLayerSourceType;
use DA\Inventory\Enums\StockAdjustmentStatus;
use DA\Inventory\Enums\StockAdjustmentType;
use DA\Inventory\Enums\StockMovementType;
use DA\Inventory\Enums\StockReferenceType;
use DA\Inventory\Models\InventoryBalance;
use DA\Inventory\Models\StockAdjustment;
use DA\Inventory\Services\Decimal;
use DA\Inventory\Services\InventoryService;
use Illuminate\Validation\ValidationException;

class ApplyStockAdjustment
{
    public function __construct(
        private InventoryService $inventory,
        private EnsureTrackedVariant $trackedVariant,
    ) {}

    public function complete(StockAdjustment $adjustment): void
    {
        if ($adjustment->status === StockAdjustmentStatus::Completed) {
            return;
        }

        if ($adjustment->status !== StockAdjustmentStatus::Draft) {
            throw ValidationException::withMessages([
                'status' => 'Only a draft adjustment can be completed.',
            ]);
        }

        $this->trackedVariant->handle($adjustment->product_variant_id);

        $tenantId = (int) auth()->user()->tenant_id;
        $balance = InventoryBalance::queries()->lockForStoreVariant(
            $tenantId,
            $adjustment->store_id,
            $adjustment->product_variant_id,
        );
        $systemQuantity = Decimal::normalize($balance->quantity);
        $difference = Decimal::sub($adjustment->adjusted_quantity, $systemQuantity);

        if ($adjustment->adjustment_type === StockAdjustmentType::Addition && Decimal::isNegative($difference)) {
            throw ValidationException::withMessages([
                'adjustment_type' => 'The adjustment type does not match the counted quantity.',
            ]);
        }

        if ($adjustment->adjustment_type === StockAdjustmentType::Subtraction && Decimal::isPositive($difference)) {
            throw ValidationException::withMessages([
                'adjustment_type' => 'The adjustment type does not match the counted quantity.',
            ]);
        }

        $adjustment->system_quantity = $systemQuantity;
        $adjustment->difference_quantity = $difference;

        if (Decimal::isZero($difference)) {
            $adjustment->total_cost = '0.0000';
            $adjustment->status = StockAdjustmentStatus::Completed;
            $adjustment->updated_by = auth()->id();
            $adjustment->save();

            return;
        }

        if (Decimal::isPositive($difference)) {
            if ($adjustment->unit_cost === null || Decimal::isNegative($adjustment->unit_cost)) {
                throw ValidationException::withMessages([
                    'unit_cost' => 'The unit cost is required when adding stock.',
                ]);
            }

            $this->inventory->increase($this->receipt($adjustment, $difference, Decimal::normalize($adjustment->unit_cost)));
            $adjustment->total_cost = Decimal::mul($difference, $adjustment->unit_cost);
        } else {
            $result = $this->inventory->decrease($this->decrease($adjustment, Decimal::abs($difference)));
            $adjustment->unit_cost = $result->unitCost;
            $adjustment->total_cost = $result->totalCost;
        }

        $adjustment->status = StockAdjustmentStatus::Completed;
        $adjustment->updated_by = auth()->id();
        $adjustment->save();
    }

    public function cancel(StockAdjustment $adjustment): void
    {
        if ($adjustment->status === StockAdjustmentStatus::Cancelled) {
            return;
        }

        if ($adjustment->status === StockAdjustmentStatus::Draft) {
            $adjustment->status = StockAdjustmentStatus::Cancelled;
            $adjustment->updated_by = auth()->id();
            $adjustment->save();

            return;
        }

        $difference = Decimal::normalize($adjustment->difference_quantity);

        if (Decimal::isPositive($difference)) {
            $this->inventory->reverseIncrease($this->receipt(
                $adjustment,
                $difference,
                Decimal::normalize($adjustment->unit_cost),
            ));
        } elseif (Decimal::isNegative($difference)) {
            $this->inventory->reverseDecrease($this->decrease($adjustment, Decimal::abs($difference)));
        }

        $adjustment->status = StockAdjustmentStatus::Cancelled;
        $adjustment->updated_by = auth()->id();
        $adjustment->save();
    }

    private function receipt(StockAdjustment $adjustment, string $quantity, string $unitCost): ReceiveStockData
    {
        return new ReceiveStockData(
            storeId: $adjustment->store_id,
            productVariantId: $adjustment->product_variant_id,
            quantity: $quantity,
            unitCost: $unitCost,
            sourceType: InventoryLayerSourceType::StockAdjustment,
            sourceId: $adjustment->id,
            sourceItemId: null,
            supplierId: null,
            movementType: StockMovementType::Adjustment,
            referenceType: StockReferenceType::StockAdjustment,
            referenceId: $adjustment->id,
            referenceItemId: null,
        );
    }

    private function decrease(StockAdjustment $adjustment, string $quantity): DecreaseStockData
    {
        return new DecreaseStockData(
            storeId: $adjustment->store_id,
            productVariantId: $adjustment->product_variant_id,
            quantity: $quantity,
            sourceType: AllocationSourceType::StockAdjustment,
            sourceId: $adjustment->id,
            sourceItemId: null,
            movementType: StockMovementType::Adjustment,
            referenceType: StockReferenceType::StockAdjustment,
            referenceId: $adjustment->id,
            referenceItemId: null,
        );
    }
}
