<?php

namespace DA\Admin\Models\Queries;

use DA\Admin\Models\Addon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder;

class AddonQueries extends BaseQueries
{
    /**
     * @return Collection<int, Addon>
     */
    public function activeOrderedByName(): Collection
    {
        return $this->eloquentBuilder()
            ->with('currency')
            ->where('is_active', true)
            ->orderBy('name')
            ->orderBy('id')
            ->get();
    }

    public function datatable(): Builder
    {
        return $this->queryBuilder()
            ->leftJoin('currencies', 'currencies.id', '=', 'addons.currency_id')
            ->select(
                'addons.id',
                'addons.name',
                'addons.code',
                'addons.description',
                'addons.billing_interval',
                'addons.price',
                'addons.currency_id',
                'addons.is_active',
                'currencies.code as currency_code',
                'addons.created_at',
                'addons.updated_at',
            )
            ->orderBy('addons.id', 'desc');
    }
}
