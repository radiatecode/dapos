<?php

namespace DA\Admin\Models\Queries;

use Illuminate\Database\Query\Builder;

class TenantQueries extends BaseQueries
{
    public function datatable(): Builder
    {
        return $this->queryBuilder()
            ->select('id', 'name', 'email', 'status', 'created_at', 'updated_at')
            ->orderBy('id', 'desc');
    }
}
