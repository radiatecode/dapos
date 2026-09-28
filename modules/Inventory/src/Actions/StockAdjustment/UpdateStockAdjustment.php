<?php

namespace DA\Inventory\Actions\StockAdjustment;

use DA\Inventory\Actions\Inventory\EnsureTrackedVariant;
use DA\Inventory\DTO\StockAdjustment\StockAdjustmentDTO;
use DA\Inventory\Enums\StockAdjustmentStatus;
use DA\Inventory\Enums\StockAdjustmentType;
use DA\Inventory\Models\StockAdjustment;
use DA\Inventory\Services\Decimal;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateStockAdjustment
{
    public function __construct(private EnsureTrackedVariant $trackedVariant) {}

    public function handle(int $id, StockAdjustmentDTO $dto): StockAdjustment
    {
        return DB::transaction(function () use ($id, $dto): StockAdjustment {
            $adjustment = StockAdjustment::queries()->lockForTenant($id);

            if ($adjustment->status !== StockAdjustmentStatus::Draft) {
                throw ValidationException::withMessages([
                    'status' => 'Only a draft adjustment can be updated.',
                ]);
            }

            $this->trackedVariant->handle($dto->productVariantId);

            $adjustment->store_id = $dto->storeId;
            $adjustment->product_variant_id = $dto->productVariantId;
            $adjustment->adjustment_date = $dto->adjustmentDate;
            $adjustment->adjustment_type = $dto->adjustmentType;
            $adjustment->reason = $dto->reason;
            $adjustment->notes = $dto->notes;
            $adjustment->adjusted_quantity = Decimal::normalize($dto->adjustedQuantity);
            $adjustment->unit_cost = $dto->adjustmentType === StockAdjustmentType::Addition && $dto->unitCost !== null
                ? Decimal::normalize($dto->unitCost)
                : null;
            $adjustment->updated_by = auth()->id();
            $adjustment->save();

            return StockAdjustment::queries()->findForDetail($adjustment->id);
        });
    }
}
