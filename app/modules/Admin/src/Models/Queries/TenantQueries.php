<?php

namespace DA\Admin\Models\Queries;

use DA\Admin\Enums\TenantStatus;
use DA\Admin\Models\Tenant;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder;

class TenantQueries extends BaseQueries
{
    /**
     * @return Collection<int, Tenant>
     */
    public function activeOrderedByName(): Collection
    {
        return $this->eloquentBuilder()
            ->where('status', TenantStatus::Active)
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->orderBy('id')
            ->get();
    }

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
