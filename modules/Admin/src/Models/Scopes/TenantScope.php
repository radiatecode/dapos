<?php

namespace DA\Admin\Models\Scopes;

use DA\Admin\Exceptions\TenantContextMissingException;
use DA\Admin\Services\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class TenantScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        $tenantId = app(TenantContext::class)->id();

        if ($tenantId === null) {
            throw new TenantContextMissingException;
        }

        $builder->where($model->qualifyColumn('tenant_id'), $tenantId);
    }
}
