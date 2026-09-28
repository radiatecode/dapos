<?php

namespace DA\Inventory\Http\Requests\Api\V1\StockAdjustment;

class StoreStockAdjustmentRequest extends StockAdjustmentRequest
{
    protected function creating(): bool
    {
        return true;
    }
}
