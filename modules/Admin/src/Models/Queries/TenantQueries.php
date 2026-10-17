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

    /**
     * Tenants that may be assigned a plan: already active, or waiting for a first subscription.
     *
     * @return Collection<int, Tenant>
     */
    public function subscribableOrderedByName(): Collection
    {
        return $this->eloquentBuilder()
            ->whereIn('status', [
                TenantStatus::Active->value,
                TenantStatus::Inactive->value,
            ])
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->orderBy('id')
            ->get();
    }

    public function datatable(): Builder
    {
        return $this->queryBuilder()
            ->leftJoin('subscriptions', 'tenants.id', '=', 'subscriptions.tenant_id')
            ->whereNull('deleted_at')
            ->select(
                'tenants.id',
                'name',
                'slug',
                'country',
                'timezone',
                'currency',
                'contact_person_name',
                'contact_person_email',
                'contact_person_phone',
                'tenants.status',
                'subscriptions.id as subscription_id',
                'tenants.created_at',
                'tenants.updated_at',
            )
            ->orderBy('id', 'desc');
    }
}
