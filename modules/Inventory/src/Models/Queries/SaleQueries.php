<?php

namespace DA\Inventory\Models\Queries;

use App\Enums\Permission;
use Carbon\CarbonImmutable;
use DA\Inventory\Enums\SalePaymentStatus;
use DA\Inventory\Enums\SaleStatus;
use DA\Inventory\Models\Sale;
use DA\Inventory\Services\Decimal;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class SaleQueries extends BaseQueries
{
    /**
     * @return LengthAwarePaginator<int, Sale>
     */
    public function paginateNewestFirst(
        int $perPage = 15,
        ?SaleStatus $status = null,
        ?SalePaymentStatus $paymentStatus = null,
        ?int $customerId = null,
        ?int $storeId = null,
        ?string $search = null,
        ?string $dateFrom = null,
        ?string $dateTo = null,
    ): LengthAwarePaginator {
        return $this->filtered($status, $paymentStatus, $customerId, $storeId, $search, $dateFrom, $dateTo)
            ->with(['customer', 'store', 'cashier'])
            ->withCount('items')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    /**
     * @return array{
     *     total_sales: int,
     *     total_amount: string,
     *     total_paid: string,
     *     total_due: string,
     *     paid_count: int,
     *     unpaid_count: int,
     *     partial_count: int
     * }
     */
    public function summary(
        ?SaleStatus $status = null,
        ?SalePaymentStatus $paymentStatus = null,
        ?int $customerId = null,
        ?int $storeId = null,
        ?string $search = null,
        ?string $dateFrom = null,
        ?string $dateTo = null,
    ): array {
        $row = $this->filtered($status, $paymentStatus, $customerId, $storeId, $search, $dateFrom, $dateTo)
            ->selectRaw(
                'COUNT(*) as total_sales,
                COALESCE(SUM(grand_total), 0) as total_amount,
                COALESCE(SUM(paid_amount), 0) as total_paid,
                COALESCE(SUM(due_amount), 0) as total_due,
                COALESCE(SUM(payment_status = ?), 0) as paid_count,
                COALESCE(SUM(payment_status = ?), 0) as unpaid_count,
                COALESCE(SUM(payment_status = ?), 0) as partial_count',
                [
                    SalePaymentStatus::Paid->value,
                    SalePaymentStatus::Unpaid->value,
                    SalePaymentStatus::Partial->value,
                ],
            )
            ->first();

        return [
            'total_sales' => (int) ($row?->total_sales ?? 0),
            'total_amount' => Decimal::normalize($row?->total_amount ?? 0),
            'total_paid' => Decimal::normalize($row?->total_paid ?? 0),
            'total_due' => Decimal::normalize($row?->total_due ?? 0),
            'paid_count' => (int) ($row?->paid_count ?? 0),
            'unpaid_count' => (int) ($row?->unpaid_count ?? 0),
            'partial_count' => (int) ($row?->partial_count ?? 0),
        ];
    }

    /**
     * One pass over the tenant's completed sales. Date buckets use half-open
     * ranges so the (tenant_id, status, sale_date) index serves the scan.
     *
     * @return array{
     *     total_amount: string,
     *     total_paid: string,
     *     total_due: string,
     *     today_amount: string,
     *     current_month_amount: string,
     *     previous_month_amount: string
     * }
     */
    public function dashboardTotals(CarbonImmutable $today): array
    {
        $currentMonthStart = $today->startOfMonth();

        $row = $this->forAuthenticatedTenant()
            ->where('status', SaleStatus::Completed)
            ->selectRaw(
                'COALESCE(SUM(grand_total), 0) as total_amount,
                COALESCE(SUM(paid_amount), 0) as total_paid,
                COALESCE(SUM(due_amount), 0) as total_due,
                COALESCE(SUM(CASE WHEN sale_date >= ? AND sale_date < ? THEN grand_total ELSE 0 END), 0) as today_amount,
                COALESCE(SUM(CASE WHEN sale_date >= ? AND sale_date < ? THEN grand_total ELSE 0 END), 0) as current_month_amount,
                COALESCE(SUM(CASE WHEN sale_date >= ? AND sale_date < ? THEN grand_total ELSE 0 END), 0) as previous_month_amount',
                [
                    $today->toDateString(),
                    $today->addDay()->toDateString(),
                    $currentMonthStart->toDateString(),
                    $currentMonthStart->addMonth()->toDateString(),
                    $currentMonthStart->subMonth()->toDateString(),
                    $currentMonthStart->toDateString(),
                ],
            )
            ->toBase()
            ->first();

        return [
            'total_amount' => Decimal::normalize($row?->total_amount ?? 0),
            'total_paid' => Decimal::normalize($row?->total_paid ?? 0),
            'total_due' => Decimal::normalize($row?->total_due ?? 0),
            'today_amount' => Decimal::normalize($row?->today_amount ?? 0),
            'current_month_amount' => Decimal::normalize($row?->current_month_amount ?? 0),
            'previous_month_amount' => Decimal::normalize($row?->previous_month_amount ?? 0),
        ];
    }

    /**
     * Days without a completed sale are not returned.
     *
     * @return Collection<int, object{sale_date: string, invoice_count: int, sales_amount: string, paid_amount: string, due_amount: string}>
     */
    public function dailyTotalsBetween(CarbonImmutable $dateFrom, CarbonImmutable $dateTo): Collection
    {
        return $this->forAuthenticatedTenant()
            ->where('status', SaleStatus::Completed)
            ->where('sale_date', '>=', $dateFrom->toDateString())
            ->where('sale_date', '<', $dateTo->addDay()->toDateString())
            ->select('sale_date')
            ->selectRaw(
                'COUNT(*) as invoice_count,
                COALESCE(SUM(grand_total), 0) as sales_amount,
                COALESCE(SUM(paid_amount), 0) as paid_amount,
                COALESCE(SUM(due_amount), 0) as due_amount',
            )
            ->groupBy('sale_date')
            ->toBase()
            ->get();
    }

    /**
     * @return Collection<int, Sale>
     */
    public function invoiceReportRows(
        ?SaleStatus $status = null,
        ?SalePaymentStatus $paymentStatus = null,
        ?int $customerId = null,
        ?int $storeId = null,
        ?string $search = null,
        ?string $dateFrom = null,
        ?string $dateTo = null,
    ): Collection {
        return $this->filtered($status, $paymentStatus, $customerId, $storeId, $search, $dateFrom, $dateTo)
            ->with(['customer', 'store'])
            ->orderByDesc('id')
            ->get();
    }

    public function findForDetail(int $id): Sale
    {
        /** @var Sale $sale */
        $sale = $this->forAuthenticatedTenant()
            ->with([
                'customer',
                'store',
                'cashier',
                'items.variant.product',
                'payments.receiver',
            ])
            ->findOrFail($id);

        return $sale;
    }

    public function lockForTenant(int $id): Sale
    {
        /** @var Sale $sale */
        $sale = $this->forAuthenticatedTenant()
            ->whereKey($id)
            ->lockForUpdate()
            ->firstOrFail();

        return $sale;
    }

    public function nextInvoiceNumber(): string
    {
        $base = 'INV-'.now()->format('Ymd');
        $latest = $this->forAuthenticatedTenant()
            ->where('invoice_number', 'like', $base.'-%')
            ->orderByDesc('invoice_number')
            ->lockForUpdate()
            ->first();

        $suffix = 1;

        if ($latest instanceof Sale) {
            $tail = substr((string) $latest->invoice_number, -4);

            if (ctype_digit($tail)) {
                $suffix = ((int) $tail) + 1;
            }
        }

        do {
            $number = $base.'-'.str_pad((string) $suffix, 4, '0', STR_PAD_LEFT);
            $suffix++;
        } while ($this->invoiceNumberExists($number));

        return $number;
    }

    public function invoiceNumberExists(string $invoiceNumber): bool
    {
        return $this->forAuthenticatedTenant()
            ->where('invoice_number', $invoiceNumber)
            ->exists();
    }

    /**
     * @return Builder<Sale>
     */
    private function filtered(
        ?SaleStatus $status,
        ?SalePaymentStatus $paymentStatus,
        ?int $customerId,
        ?int $storeId,
        ?string $search,
        ?string $dateFrom,
        ?string $dateTo,
    ): Builder {
        $term = trim((string) $search);

        return $this->forAuthenticatedTenant()
            ->when($status !== null, fn (Builder $query) => $query->where('status', $status))
            ->when($paymentStatus !== null, fn (Builder $query) => $query->where('payment_status', $paymentStatus))
            ->when($customerId !== null, fn (Builder $query) => $query->where('customer_id', $customerId))
            ->when($storeId !== null, fn (Builder $query) => $query->where('store_id', $storeId))
            ->when(
                $dateFrom !== null && $dateTo !== null,
                fn (Builder $query) => $query->whereDate('sale_date', '>=', $dateFrom)->whereDate('sale_date', '<=', $dateTo)
            )
            ->when(
                auth()->user()->hasPermission(Permission::OnlyTodayData->value),
                fn (Builder $query) => $query->whereDate('sale_date', '=', now()->toDateString())
            )
            ->when($term !== '', function (Builder $query) use ($term): void {
                $like = '%'.addcslashes($term, '%_\\').'%';
                $query->where(function (Builder $inner) use ($like): void {
                    $inner->where('invoice_number', 'like', $like)
                        ->orWhereHas('customer', fn (Builder $customer) => $customer->where('name', 'like', $like));
                });
            })
            ->when(
                auth()->user()->hasPermission(Permission::OnlyCreatorUpdate->value),
                fn (Builder $query) => $query->where('cashier_id', auth()->user()->id)
            );
    }

    /**
     * @return Builder<Sale>
     */
    private function forAuthenticatedTenant(): Builder
    {
        return $this->eloquentBuilder()->where('tenant_id', auth()->user()->tenant_id);
    }
}
