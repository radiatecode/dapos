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
        $guard = auth()->guard();

        // The authenticatable model boots while the guard is still resolving
        // the user. hasUser() reads the cached user and does not start another lookup.
        if (! $guard->hasUser()) {
            return;
        }

        $actor = $guard->user();

        if (! $actor instanceof User || $actor->tenant_id === null) {
            return;
        }

        $builder->where($model->qualifyColumn('tenant_id'), $actor->tenant_id);
    }
}
