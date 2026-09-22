<?php

namespace App\Models\Concerns;

use DA\Admin\Exceptions\TenantContextMissingException;
use DA\Admin\Models\Scopes\TenantScope;
use DA\Admin\Models\Tenant;
use DA\Admin\Services\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function (Model $model): void {
            $context = app(TenantContext::class);

            if ($context->has()) {
                $model->setAttribute('tenant_id', $context->id());

                return;
            }

            if ($model->getAttribute('tenant_id') === null) {
                throw new TenantContextMissingException;
            }
        });
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    #[Scope]
    protected function forTenant(Builder $query, int $tenantId): void
    {
        $query->withoutGlobalScope(TenantScope::class)
            ->where($query->getModel()->qualifyColumn('tenant_id'), $tenantId);
    }

    #[Scope]
    protected function withoutTenantScope(Builder $query): void
    {
        $query->withoutGlobalScope(TenantScope::class);
    }
}
