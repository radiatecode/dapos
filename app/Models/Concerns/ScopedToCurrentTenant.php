<?php

namespace App\Models\Concerns;

use App\Models\Scopes\CurrentTenantScope;
use DA\Admin\Models\Tenant;
use DA\Admin\Services\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait ScopedToCurrentTenant
{
    public static function bootScopedToCurrentTenant(): void
    {
        if (auth()->check()) {
            static::creating(function (Model $model): void {
                $model->setAttribute('tenant_id', auth()->user()->tenant_id);

                // $context = app(TenantContext::class);

                // if ($context->has()) {
                //     $model->setAttribute('tenant_id', $context->id());
                // }
            });

            static::addGlobalScope(new CurrentTenantScope);
        }
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
