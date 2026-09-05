<?php

namespace DA\Admin\Models\Queries;

use DA\Admin\Models\Feature;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder;

class FeatureQueries extends BaseQueries
{
    public function datatable(): Builder
    {
        return $this->queryBuilder()
            ->select(
                'id',
                'name',
                'code',
                'type',
                'description',
                'created_at',
                'updated_at',
            )
            ->orderBy('id', 'desc');
    }

    /**
     * @return Collection<int, Feature>
     */
    public function orderedForAssignment(): Collection
    {
        return $this->eloquentBuilder()
            ->orderBy('name')
            ->orderBy('id')
            ->get();
    }
}
