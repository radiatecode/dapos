<?php

namespace DA\Admin\Models\Queries;

use Illuminate\Database\Query\Builder;

class SubscriptionPaymentQueries extends BaseQueries
{
    public function datatable(): Builder
    {
        return $this->queryBuilder()
            ->leftJoin('tenants', 'tenants.id', '=', 'subscription_payments.tenant_id')
            ->leftJoin('invoices', 'invoices.id', '=', 'subscription_payments.invoice_id')
            ->leftJoin('currencies', 'currencies.id', '=', 'subscription_payments.currency_id')
            ->select(
                'subscription_payments.id',
                'subscription_payments.tenant_id',
                'subscription_payments.invoice_id',
                'subscription_payments.amount',
                'subscription_payments.payment_method',
                'subscription_payments.transaction_id',
                'subscription_payments.status',
                'subscription_payments.paid_at',
                'subscription_payments.created_at',
                'tenants.name as tenant_name',
                'invoices.invoice_number',
                'currencies.code as currency_code',
            )
            ->orderBy('subscription_payments.id', 'desc');
    }
}
