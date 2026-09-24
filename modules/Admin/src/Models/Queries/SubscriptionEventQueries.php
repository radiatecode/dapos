<?php

namespace DA\Admin\Models\Queries;

use Illuminate\Database\Query\Builder;

class SubscriptionEventQueries extends BaseQueries
{
    public function datatable(): Builder
    {
        return $this->queryBuilder()
            ->leftJoin('tenants', 'tenants.id', '=', 'subscription_events.tenant_id')
            ->leftJoin('subscriptions', 'subscriptions.id', '=', 'subscription_events.subscription_id')
            ->leftJoin('plans', 'plans.id', '=', 'subscriptions.plan_id')
            ->select(
                'subscription_events.id',
                'subscription_events.tenant_id',
                'subscription_events.subscription_id',
                'subscription_events.event_type',
                'subscription_events.old_status',
                'subscription_events.new_status',
                'subscription_events.occurred_at',
                'tenants.name as tenant_name',
                'plans.name as plan_name',
            )
            ->orderBy('subscription_events.occurred_at', 'desc')
            ->orderBy('subscription_events.id', 'desc');
    }
}
