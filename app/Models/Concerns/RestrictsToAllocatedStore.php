<?php

namespace App\Models\Concerns;

use App\Models\Scopes\AllocatedStoreScope;
use App\Services\AllocatedStores;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

trait RestrictsToAllocatedStore
{
    public static function bootRestrictsToAllocatedStore(): void
    {
        static::addGlobalScope(new AllocatedStoreScope(static::allocatedStoreColumn()));

        static::saving(function (Model $model): void {
            $column = static::allocatedStoreColumn();
            $storeId = $model->getAttribute($column);

            if (! is_numeric($storeId) || AllocatedStores::allows((int) $storeId)) {
                return;
            }

            throw ValidationException::withMessages([
                $column => 'The selected store is not allocated to you.',
            ]);
        });
    }

    protected static function allocatedStoreColumn(): string
    {
        return 'store_id';
    }
}
