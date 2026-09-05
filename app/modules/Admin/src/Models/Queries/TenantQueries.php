<?php

namespace DA\Admin\Models\Queries;

use Illuminate\Database\Query\Builder;

class TenantQueries extends BaseQueries
{
    public function datatable(): Builder
    {
        return $this->queryBuilder()
            ->whereNull('deleted_at')
            ->select(
                'id',
                'name',
                'slug',
                'country',
                'timezone',
                'currency',
                'contact_person_name',
                'contact_person_email',
                'contact_person_phone',
                'status',
                'created_at',
                'updated_at',
            )
            ->orderBy('id', 'desc');
    }
}
