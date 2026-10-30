<?php

namespace DA\Inventory\Models\Queries;

use Carbon\CarbonImmutable;
use DA\Inventory\DTO\Report\PurchaseReportFilterDTO;
use DA\Inventory\Enums\PurchasePaymentStatus;
use DA\Inventory\Enums\PurchaseStatus;
use DA\Inventory\Models\Purchase;
use DA\Inventory\Services\Decimal;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class PurchaseQueries extends BaseQueries
{
    /**
     * @return LengthAwarePaginator<int, Purchase>
     */
    public function paginateNewestFirst(
        int $perPage = 15,
        ?PurchaseStatus $status = null,
        ?PurchasePaymentStatus $paymentStatus = null,
        ?int $supplierId = null,
        ?string $search = null,
        ?string $dateFrom = null,
        ?string $dateTo = null,
    ): LengthAwarePaginator {
        return $this->filtered($status, $paymentStatus, $supplierId, $search, $dateFrom, $dateTo)
            ->with(['supplier', 'store'])
            ->withCount('items')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    /**
     * @return array{
     *     total_purchases: int,
     *     total_amount: string,
     *     received_count: int,
     *     pending_count: int,
     *     ordered_count: int,
     *     paid_count: int,
     *     unpaid_count: int,
     *     partial_count: int
     * }
     */
    public function summary(): array
    {
        $row = $this->forAuthenticatedTenant()
            ->selectRaw(
                'COUNT(*) as total_purchases,
                COALESCE(SUM(grand_total), 0) as total_amount,
                COALESCE(SUM(status = ?), 0) as received_count,
                COALESCE(SUM(status = ?), 0) as pending_count,
                COALESCE(SUM(status = ?), 0) as ordered_count,
                COALESCE(SUM(payment_status = ?), 0) as paid_count,
                COALESCE(SUM(payment_status = ?), 0) as unpaid_count,
                COALESCE(SUM(payment_status = ?), 0) as partial_count',
                [
                    PurchaseStatus::Received->value,
                    PurchaseStatus::Pending->value,
                    PurchaseStatus::Ordered->value,
                    PurchasePaymentStatus::Paid->value,
                    PurchasePaymentStatus::Unpaid->value,
                    PurchasePaymentStatus::Partial->value,
                ],
            )
            ->first();

        return [
            'total_purchases' => (int) ($row?->total_purchases ?? 0),
            'total_amount' => Decimal::normalize($row?->total_amount ?? 0),
            'received_count' => (int) ($row?->received_count ?? 0),
            'pending_count' => (int) ($row?->pending_count ?? 0),
            'ordered_count' => (int) ($row?->ordered_count ?? 0),
            'paid_count' => (int) ($row?->paid_count ?? 0),
            'unpaid_count' => (int) ($row?->unpaid_count ?? 0),
            'partial_count' => (int) ($row?->partial_count ?? 0),
        ];
    }

    /**
     * Only received purchases count, because pending and ordered purchases
     * have not brought stock in yet.
     *
     * @return array{total_amount: string, today_amount: string, current_month_amount: string}
     */
    public function dashboardReceivedTotals(CarbonImmutable $today): array
    {
        $currentMonthStart = $today->startOfMonth();

        $row = $this->forAuthenticatedTenant()
            ->where('status', PurchaseStatus::Received)
            ->selectRaw(
                'COALESCE(SUM(grand_total), 0) as total_amount,
                COALESCE(SUM(CASE WHEN order_date >= ? AND order_date < ? THEN grand_total ELSE 0 END), 0) as today_amount,
                COALESCE(SUM(CASE WHEN order_date >= ? AND order_date < ? THEN grand_total ELSE 0 END), 0) as current_month_amount',
                [
                    $today->toDateString(),
                    $today->addDay()->toDateString(),
                    $currentMonthStart->toDateString(),
                    $currentMonthStart->addMonth()->toDateString(),
                ],
            )
            ->toBase()
            ->first();

        return [
            'total_amount' => Decimal::normalize($row?->total_amount ?? 0),
            'today_amount' => Decimal::normalize($row?->today_amount ?? 0),
            'current_month_amount' => Decimal::normalize($row?->current_month_amount ?? 0),
        ];
    }

    /**
     * @return LengthAwarePaginator<int, Purchase>
     */
    public function paginateReport(PurchaseReportFilterDTO $filters, int $perPage): LengthAwarePaginator
    {
        return $this->reportRowsQuery($filters)->paginate($perPage);
    }

    /**
     * Payment status is omitted so the report totals stay in place when a payment pill filters the list.
     *
     * @return array{
     *     total_purchases: int,
     *     total_amount: string,
     *     total_paid: string,
     *     total_due: string,
     *     paid_count: int,
     *     unpaid_count: int,
     *     partial_count: int
     * }
     */
    public function reportTotals(PurchaseReportFilterDTO $filters): array
    {
        $row = $this->reportFiltered($filters, false)
            ->toBase()
            ->selectRaw(
                'COUNT(*) as total_purchases,
                COALESCE(SUM(grand_total), 0) as total_amount,
                COALESCE(SUM(paid_amount), 0) as total_paid,
                COALESCE(SUM(due_amount), 0) as total_due,
                COALESCE(SUM(payment_status = ?), 0) as paid_count,
                COALESCE(SUM(payment_status = ?), 0) as unpaid_count,
                COALESCE(SUM(payment_status = ?), 0) as partial_count',
                [
                    PurchasePaymentStatus::Paid->value,
                    PurchasePaymentStatus::Unpaid->value,
                    PurchasePaymentStatus::Partial->value,
                ],
            )
            ->first();

        return [
            'total_purchases' => (int) ($row?->total_purchases ?? 0),
            'total_amount' => Decimal::normalize($row?->total_amount ?? 0),
            'total_paid' => Decimal::normalize($row?->total_paid ?? 0),
            'total_due' => Decimal::normalize($row?->total_due ?? 0),
            'paid_count' => (int) ($row?->paid_count ?? 0),
            'unpaid_count' => (int) ($row?->unpaid_count ?? 0),
            'partial_count' => (int) ($row?->partial_count ?? 0),
        ];
    }

    /**
     * @return Collection<int, Purchase>
     */
    public function reportRows(PurchaseReportFilterDTO $filters): Collection
    {
        return $this->reportRowsQuery($filters)->get();
    }

    public function findForTenant(int $id): Purchase
    {
        return $this->forAuthenticatedTenant()->findOrFail($id);
    }

    public function findForDetail(int $id): Purchase
    {
        return $this->forAuthenticatedTenant()
            ->with(['supplier', 'store', 'creator', 'items.variant.product', 'payments.recorder'])
            ->findOrFail($id);
    }

    public function lockForTenant(int $id): Purchase
    {
        /** @var Purchase $purchase */
        $purchase = $this->forAuthenticatedTenant()
            ->whereKey($id)
            ->lockForUpdate()
            ->firstOrFail();

        return $purchase;
    }

    public function poNumberExists(string $poNumber, ?int $ignoreId = null): bool
    {
        return $this->forAuthenticatedTenant()
            ->where('po_number', $poNumber)
            ->when($ignoreId !== null, fn (Builder $query) => $query->where('id', '!=', $ignoreId))
            ->exists();
    }

    public function nextPoNumber(): string
    {
        $base = 'PO-'.now()->format('Ymd');
        $suffix = $this->forAuthenticatedTenant()
            ->where('po_number', 'like', $base.'-%')
            ->count() + 1;

        do {
            $number = $base.'-'.str_pad((string) $suffix, 4, '0', STR_PAD_LEFT);
            $suffix++;
        } while ($this->poNumberExists($number));

        return $number;
    }

    /**
     * @return Builder<Purchase>
     */
    private function filtered(
        ?PurchaseStatus $status,
        ?PurchasePaymentStatus $paymentStatus,
        ?int $supplierId,
        ?string $search,
        ?string $dateFrom,
        ?string $dateTo,
    ): Builder {
        $term = trim((string) $search);

        return $this->forAuthenticatedTenant()
            ->when($status !== null, fn (Builder $query) => $query->where('status', $status))
            ->when($paymentStatus !== null, fn (Builder $query) => $query->where('payment_status', $paymentStatus))
            ->when($supplierId !== null, fn (Builder $query) => $query->where('supplier_id', $supplierId))
            ->when($dateFrom !== null, fn (Builder $query) => $query->whereDate('order_date', '>=', $dateFrom))
            ->when($dateTo !== null, fn (Builder $query) => $query->whereDate('order_date', '<=', $dateTo))
            ->when($term !== '', function (Builder $query) use ($term): void {
                $like = '%'.addcslashes($term, '%_\\').'%';
                $query->where(function (Builder $inner) use ($like): void {
                    $inner->where('po_number', 'like', $like)
                        ->orWhereHas('supplier', fn (Builder $supplier) => $supplier->where('name', 'like', $like));
                });
            });
    }

    /**
     * @return Builder<Purchase>
     */
    private function reportRowsQuery(PurchaseReportFilterDTO $filters): Builder
    {
        return $this->reportFiltered($filters, true)
            ->with(['supplier', 'store'])
            ->orderByDesc('order_date')
            ->orderByDesc('id');
    }

    /**
     * @return Builder<Purchase>
     */
    private function reportFiltered(PurchaseReportFilterDTO $filters, bool $includePayment): Builder
    {
        $term = trim((string) $filters->search);

        return $this->forAuthenticatedTenant()
            ->when($includePayment && $filters->paymentStatus !== null, fn (Builder $query) => $query->where('payment_status', $filters->paymentStatus))
            ->when($filters->supplierId !== null, fn (Builder $query) => $query->where('supplier_id', $filters->supplierId))
            ->when($filters->storeId !== null, fn (Builder $query) => $query->where('store_id', $filters->storeId))
            ->when(filled($filters->dateFrom), fn (Builder $query) => $query->whereDate('order_date', '>=', $filters->dateFrom))
            ->when(filled($filters->dateTo), fn (Builder $query) => $query->whereDate('order_date', '<=', $filters->dateTo))
            ->when($term !== '', function (Builder $query) use ($term): void {
                $like = '%'.addcslashes($term, '%_\\').'%';
                $query->where(function (Builder $inner) use ($like): void {
                    $inner->where('po_number', 'like', $like)
                        ->orWhereHas('supplier', fn (Builder $supplier) => $supplier->where('name', 'like', $like));
                });
            });
    }

    /**
     * @return Builder<Purchase>
     */
    private function forAuthenticatedTenant(): Builder
    {
        return $this->eloquentBuilder()->where('tenant_id', auth()->user()->tenant_id);
    }
}
