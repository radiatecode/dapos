<?php

namespace DA\Inventory\Actions\StockAdjustment;

use DA\Inventory\Actions\Inventory\EnsureTrackedVariant;
use DA\Inventory\DTO\StockAdjustment\StockAdjustmentDTO;
use DA\Inventory\Enums\StockAdjustmentStatus;
use DA\Inventory\Enums\StockAdjustmentType;
use DA\Inventory\Models\StockAdjustment;
use DA\Inventory\Services\Decimal;
use Illuminate\Support\Facades\DB;

class CreateStockAdjustment
{
    public function __construct(
        private EnsureTrackedVariant $trackedVariant,
        private ApplyStockAdjustment $apply,
    ) {}

    public function handle(StockAdjustmentDTO $dto): StockAdjustment
    {
        return DB::transaction(function () use ($dto): StockAdjustment {
            $this->trackedVariant->handle($dto->productVariantId);

            $adjustment = new StockAdjustment;
            $adjustment->tenant_id = auth()->user()->tenant_id;
            $adjustment->store_id = $dto->storeId;
            $adjustment->product_variant_id = $dto->productVariantId;
            $adjustment->reference_number = StockAdjustment::queries()->nextReferenceNumber();
            $adjustment->adjustment_date = $dto->adjustmentDate;
            $adjustment->adjustment_type = $dto->adjustmentType;
            $adjustment->reason = $dto->reason;
            $adjustment->notes = $dto->notes;
            $adjustment->adjusted_quantity = Decimal::normalize($dto->adjustedQuantity);
            $adjustment->unit_cost = $dto->adjustmentType === StockAdjustmentType::Addition && $dto->unitCost !== null
                ? Decimal::normalize($dto->unitCost)
                : null;
            $adjustment->status = StockAdjustmentStatus::Draft;
            $adjustment->created_by = auth()->id();
            $adjustment->updated_by = auth()->id();
            $adjustment->save();

            if ($dto->status === StockAdjustmentStatus::Completed) {
                $locked = StockAdjustment::queries()->lockForTenant($adjustment->id);
                $this->apply->complete($locked);
            }

            return StockAdjustment::queries()->findForDetail($adjustment->id);
        });
    }
}
