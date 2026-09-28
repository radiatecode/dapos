<?php

namespace DA\Inventory\Actions\StockAdjustment;

use DA\Inventory\Enums\StockAdjustmentStatus;
use DA\Inventory\Models\StockAdjustment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateStockAdjustmentStatus
{
    public function __construct(private ApplyStockAdjustment $apply) {}

    public function handle(int $id, StockAdjustmentStatus $status): StockAdjustment
    {
        return DB::transaction(function () use ($id, $status): StockAdjustment {
            $adjustment = StockAdjustment::queries()->lockForTenant($id);

            if ($status === StockAdjustmentStatus::Completed) {
                $this->apply->complete($adjustment);
            } elseif ($status === StockAdjustmentStatus::Cancelled) {
                $this->apply->cancel($adjustment);
            } else {
                throw ValidationException::withMessages([
                    'status' => 'The selected status is invalid.',
                ]);
            }

            return StockAdjustment::queries()->findForDetail($adjustment->id);
        });
    }
}
