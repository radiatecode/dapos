<?php

namespace App\Models\Scopes;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class CurrentTenantScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        $actor = auth()->user();

        if (! $actor instanceof User || $actor->tenant_id === null) {
            return;
        }

        $tenantId = $actor->tenant_id;

        $builder->where($model->qualifyColumn('tenant_id'), $tenantId);
    }
}
