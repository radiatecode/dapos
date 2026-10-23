<?php

namespace App\Models\Concerns;

use App\Models\Scopes\AllocatedStockTransferScope;
use App\Services\AllocatedStores;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

trait RestrictsToAllocatedStockTransfer
{
    public static function bootRestrictsToAllocatedStockTransfer(): void
    {
        static::addGlobalScope(new AllocatedStockTransferScope);

        static::saving(function (Model $model): void {
            foreach (['from_store_id', 'to_store_id'] as $column) {
                $storeId = $model->getAttribute($column);

                if (! is_numeric($storeId) || AllocatedStores::allows((int) $storeId)) {
                    continue;
                }

                throw ValidationException::withMessages([
                    $column => 'The selected store is not allocated to you.',
                ]);
            }
        });
    }
}
