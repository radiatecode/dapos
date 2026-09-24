<?php

namespace DA\Admin\Models\Queries;

use DA\Admin\Enums\SubscriptionStatus;
use DA\Admin\Models\Subscription;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder;

class SubscriptionQueries extends BaseQueries
{
    public function datatable(): Builder
    {
        return $this->queryBuilder()
            ->leftJoin('tenants', 'tenants.id', '=', 'subscriptions.tenant_id')
            ->leftJoin('plans', 'plans.id', '=', 'subscriptions.plan_id')
            ->select(
                'subscriptions.id',
                'subscriptions.tenant_id',
                'subscriptions.plan_id',
                'subscriptions.status',
                'subscriptions.starts_at',
                'subscriptions.trial_ends_at',
                'subscriptions.current_period_start',
                'subscriptions.current_period_end',
                'subscriptions.cancel_at_period_end',
                'tenants.name as tenant_name',
                'plans.name as plan_name',
                'subscriptions.created_at',
                'subscriptions.updated_at',
            )
            ->orderBy('subscriptions.id', 'desc');
    }

    public function currentForTenant(int $tenantId): ?Subscription
    {
        return $this->eloquentBuilder()
            ->where('tenant_id', $tenantId)
            ->whereIn('status', [
                SubscriptionStatus::Trialing->value,
                SubscriptionStatus::Active->value,
                SubscriptionStatus::PastDue->value,
                SubscriptionStatus::Paused->value,
            ])
            ->orderByDesc('id')
            ->first();
    }

    /**
     * @return Collection<int, Subscription>
     */
    public function dueForPeriodClose(): Collection
    {
        return $this->eloquentBuilder()
            ->whereIn('status', [
                SubscriptionStatus::Trialing->value,
                SubscriptionStatus::Active->value,
            ])
            ->whereNotNull('current_period_end')
            ->where('current_period_end', '<=', now())
            ->orderBy('id')
            ->get();
    }

    /**
     * @return Collection<int, Subscription>
     */
    public function dueToConcludeGrace(): Collection
    {
        return $this->eloquentBuilder()
            ->where('status', SubscriptionStatus::PastDue->value)
            ->whereNotNull('grace_ends_at')
            ->where('grace_ends_at', '<=', now())
            ->orderBy('id')
            ->get();
    }
}
