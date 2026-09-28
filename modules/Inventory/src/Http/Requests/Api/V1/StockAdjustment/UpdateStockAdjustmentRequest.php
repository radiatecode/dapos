<?php

namespace DA\Inventory\Http\Requests\Api\V1\StockAdjustment;

class UpdateStockAdjustmentRequest extends StockAdjustmentRequest
{
    protected function creating(): bool
    {
        return false;
    }
}
