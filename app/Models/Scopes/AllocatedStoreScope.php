<?php

namespace App\Models\Scopes;

use App\Services\AllocatedStores;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class AllocatedStoreScope implements Scope
{
    public function __construct(private string $column = 'store_id') {}

    /**
     * Limit rows to the stores allocated to the authenticated user.
     */
    public function apply(Builder $builder, Model $model): void
    {
        $storeIds = AllocatedStores::ids();

        if ($storeIds === null) {
            return;
        }

        $builder->whereIn($model->qualifyColumn($this->column), $storeIds);
    }
}
