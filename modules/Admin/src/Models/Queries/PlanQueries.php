<?php

namespace DA\Admin\Models\Queries;

use DA\Admin\Models\Plan;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder;

class PlanQueries extends BaseQueries
{
    /**
     * @return Collection<int, Plan>
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
            ->leftJoin('currencies', 'currencies.id', '=', 'plans.currency_id')
            ->select(
                'plans.id',
                'plans.name',
                'plans.code',
                'plans.billing_interval',
                'plans.price',
                'plans.trial_days',
                'plans.is_active',
                'currencies.code as currency_code',
                'plans.created_at',
                'plans.updated_at',
            )
            ->orderBy('plans.id', 'desc');
    }
}
