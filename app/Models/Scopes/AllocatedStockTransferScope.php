<?php

namespace App\Models\Scopes;

use App\Services\AllocatedStores;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class AllocatedStockTransferScope implements Scope
{
    /**
     * A transfer is visible only when both stores are allocated to the user.
     */
    public function apply(Builder $builder, Model $model): void
    {
        $storeIds = AllocatedStores::ids();

        if ($storeIds === null) {
            return;
        }

        $builder->whereIn($model->qualifyColumn('from_store_id'), $storeIds)
            ->whereIn($model->qualifyColumn('to_store_id'), $storeIds);
    }
}
