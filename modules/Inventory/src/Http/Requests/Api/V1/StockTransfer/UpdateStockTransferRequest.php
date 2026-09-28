<?php

namespace DA\Inventory\Http\Requests\Api\V1\StockTransfer;

class UpdateStockTransferRequest extends StockTransferRequest
{
    protected function creating(): bool
    {
        return false;
    }
}
