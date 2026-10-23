<?php

namespace App\Models\Concerns;

use App\Models\Scopes\CurrentTenantScope;
use App\Models\User;
use DA\Admin\Models\Tenant;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait ScopedToCurrentTenant
{
    public static function bootScopedToCurrentTenant(): void
    {
        static::creating(function (Model $model): void {
            if ($model->getAttribute('tenant_id') !== null) {
                return;
            }

            $guard = auth()->guard();

            if (! $guard->hasUser()) {
                return;
            }

            $actor = $guard->user();

            if (! $actor instanceof User || $actor->tenant_id === null) {
                return;
            }

            $model->setAttribute('tenant_id', $actor->tenant_id);
        });

        static::addGlobalScope(new CurrentTenantScope);
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    #[Scope]
    protected function withoutTenantScope(Builder $query): void
    {
        $query->withoutGlobalScope(CurrentTenantScope::class);
    }
}
