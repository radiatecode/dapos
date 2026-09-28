<?php

namespace DA\Inventory\Http\Requests\Api\V1\StockTransfer;

class StoreStockTransferRequest extends StockTransferRequest
{
    protected function creating(): bool
    {
        return true;
    }
}
