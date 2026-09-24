<?php

namespace DA\Admin\Models\Queries;

use DA\Admin\Enums\InvoiceStatus;
use DA\Admin\Models\Invoice;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder;

class InvoiceQueries extends BaseQueries
{
    public function datatable(): Builder
    {
        return $this->queryBuilder()
            ->leftJoin('tenants', 'tenants.id', '=', 'invoices.tenant_id')
            ->leftJoin('currencies', 'currencies.id', '=', 'invoices.currency_id')
            ->select(
                'invoices.id',
                'invoices.tenant_id',
                'invoices.subscription_id',
                'invoices.invoice_number',
                'invoices.status',
                'invoices.billing_period_start',
                'invoices.billing_period_end',
                'invoices.total_amount',
                'invoices.due_date',
                'invoices.paid_at',
                'tenants.name as tenant_name',
                'currencies.code as currency_code',
                'invoices.created_at',
                'invoices.updated_at',
            )
            ->orderBy('invoices.id', 'desc');
    }

    public function latestNumberForPrefix(string $prefix): ?string
    {
        return $this->eloquentBuilder()
            ->where('invoice_number', 'like', $prefix.'%')
            ->orderByDesc('invoice_number')
            ->lockForUpdate()
            ->value('invoice_number');
    }

    public function openForSubscriptionPeriod(int $subscriptionId, DateTimeInterface $periodStart): ?Invoice
    {
        return $this->eloquentBuilder()
            ->where('subscription_id', $subscriptionId)
            ->where('billing_period_start', $periodStart)
            ->where('status', InvoiceStatus::Open->value)
            ->first();
    }

    public function paidForSubscriptionPeriod(int $subscriptionId, DateTimeInterface $periodStart): ?Invoice
    {
        return $this->eloquentBuilder()
            ->where('subscription_id', $subscriptionId)
            ->where('billing_period_start', $periodStart)
            ->where('status', InvoiceStatus::Paid->value)
            ->first();
    }

    /**
     * @return Collection<int, Invoice>
     */
    public function billingHistoryForTenant(int $tenantId): Collection
    {
        return $this->eloquentBuilder()
            ->with(['currency', 'subscription.plan', 'payments'])
            ->where('tenant_id', $tenantId)
            ->orderByDesc('id')
            ->get();
    }

    /**
     * @return Collection<int, Invoice>
     */
    public function billingHistoryForSubscription(int $subscriptionId): Collection
    {
        return $this->eloquentBuilder()
            ->with(['currency', 'payments'])
            ->where('subscription_id', $subscriptionId)
            ->orderByDesc('id')
            ->get();
    }
}
