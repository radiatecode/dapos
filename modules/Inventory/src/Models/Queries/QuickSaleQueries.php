<?php

namespace DA\Inventory\Models\Queries;

use Carbon\CarbonImmutable;
use DA\Inventory\DTO\QuickSale\QuickSaleFilterDTO;
use DA\Inventory\Enums\PaymentMethod;
use DA\Inventory\Models\QuickSale;
use DA\Inventory\Services\Decimal;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;

class QuickSaleQueries extends BaseQueries
{
    /**
     * @return LengthAwarePaginator<int, QuickSale>
     */
    public function paginateNewestFirst(QuickSaleFilterDTO $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->filtered($filters)
            ->with(['store', 'cashier'])
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    /**
     * @return array{total_quick_sales: int, total_amount: string, total_items: int, average_amount: string}
     */
    public function summary(QuickSaleFilterDTO $filters): array
    {
        $row = $this->filtered($filters)
            ->selectRaw(
                'COUNT(*) as total_quick_sales,
                COALESCE(SUM(grand_total), 0) as total_amount,
                COALESCE(SUM(total_items), 0) as total_items',
            )
            ->toBase()
            ->first();

        $count = (int) ($row?->total_quick_sales ?? 0);
        $amount = Decimal::normalize($row?->total_amount ?? 0);

        return [
            'total_quick_sales' => $count,
            'total_amount' => $amount,
            'total_items' => (int) ($row?->total_items ?? 0),
            'average_amount' => Decimal::div($amount, $count),
        ];
    }

    /**
     * @return array{quick_sales_count: int, total_amount: string, total_items: int, cash_amount: string}
     */
    public function totalsForDay(CarbonImmutable $day): array
    {
        $row = $this->forAuthenticatedTenant()
            ->where('sale_date', '>=', $day->toDateString())
            ->where('sale_date', '<', $day->addDay()->toDateString())
            ->selectRaw(
                'COUNT(*) as quick_sales_count,
                COALESCE(SUM(grand_total), 0) as total_amount,
                COALESCE(SUM(total_items), 0) as total_items,
                COALESCE(SUM(CASE WHEN payment_method = ? THEN grand_total ELSE 0 END), 0) as cash_amount',
                [PaymentMethod::CASH->value],
            )
            ->toBase()
            ->first();

        return [
            'quick_sales_count' => (int) ($row?->quick_sales_count ?? 0),
            'total_amount' => Decimal::normalize($row?->total_amount ?? 0),
            'total_items' => (int) ($row?->total_items ?? 0),
            'cash_amount' => Decimal::normalize($row?->cash_amount ?? 0),
        ];
    }

    /**
     * @return LengthAwarePaginator<int, object>
     */
    public function paginateDailyReport(QuickSaleFilterDTO $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->dailyReport($filters)->paginate($perPage);
    }

    /**
     * @return Collection<int, object>
     */
    public function dailyReportRows(QuickSaleFilterDTO $filters): Collection
    {
        return $this->dailyReport($filters)->get();
    }

    /**
     * @return array{
     *     total_quick_sales: int,
     *     total_items: int,
     *     total_amount: string,
     *     cash_amount: string,
     *     card_amount: string,
     *     bank_transfer_amount: string,
     *     mobile_banking_amount: string,
     *     average_amount: string
     * }
     */
    public function dailyReportTotals(QuickSaleFilterDTO $filters): array
    {
        $row = $this->filtered($filters)
            ->selectRaw('COUNT(*) as total_quick_sales, '.$this->reportAggregates(), $this->paymentMethodBindings())
            ->toBase()
            ->first();

        $count = (int) ($row?->total_quick_sales ?? 0);
        $amount = Decimal::normalize($row?->total_amount ?? 0);

        return [
            'total_quick_sales' => $count,
            'total_items' => (int) ($row?->total_items ?? 0),
            'total_amount' => $amount,
            'cash_amount' => Decimal::normalize($row?->cash_amount ?? 0),
            'card_amount' => Decimal::normalize($row?->card_amount ?? 0),
            'bank_transfer_amount' => Decimal::normalize($row?->bank_transfer_amount ?? 0),
            'mobile_banking_amount' => Decimal::normalize($row?->mobile_banking_amount ?? 0),
            'average_amount' => Decimal::div($amount, $count),
        ];
    }

    public function findForDetail(int $id): QuickSale
    {
        /** @var QuickSale $quickSale */
        $quickSale = $this->forAuthenticatedTenant()
            ->with(['store', 'cashier', 'items'])
            ->findOrFail($id);

        return $quickSale;
    }

    public function nextInvoiceNumber(): string
    {
        $base = 'QS-'.now()->format('Ymd');
        $latest = $this->forAuthenticatedTenant()
            ->where('invoice_number', 'like', $base.'-%')
            ->orderByDesc('invoice_number')
            ->lockForUpdate()
            ->first();

        $suffix = 1;

        if ($latest instanceof QuickSale) {
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

    private function dailyReport(QuickSaleFilterDTO $filters): QueryBuilder
    {
        return $this->filtered($filters)
            ->select('sale_date')
            ->selectRaw('COUNT(*) as quick_sales_count, '.$this->reportAggregates(), $this->paymentMethodBindings())
            ->groupBy('sale_date')
            ->orderByDesc('sale_date')
            ->toBase();
    }

    private function reportAggregates(): string
    {
        return 'COALESCE(SUM(total_items), 0) as total_items,
            COALESCE(SUM(grand_total), 0) as total_amount,
            COALESCE(SUM(CASE WHEN payment_method = ? THEN grand_total ELSE 0 END), 0) as cash_amount,
            COALESCE(SUM(CASE WHEN payment_method = ? THEN grand_total ELSE 0 END), 0) as card_amount,
            COALESCE(SUM(CASE WHEN payment_method = ? THEN grand_total ELSE 0 END), 0) as bank_transfer_amount,
            COALESCE(SUM(CASE WHEN payment_method = ? THEN grand_total ELSE 0 END), 0) as mobile_banking_amount';
    }

    /**
     * @return list<int>
     */
    private function paymentMethodBindings(): array
    {
        return [
            PaymentMethod::CASH->value,
            PaymentMethod::CARD->value,
            PaymentMethod::BANK_TRANSFER->value,
            PaymentMethod::MOBILE_BANKING->value,
        ];
    }

    /**
     * @return Builder<QuickSale>
     */
    private function filtered(QuickSaleFilterDTO $filters): Builder
    {
        $term = trim((string) $filters->search);

        return $this->forAuthenticatedTenant()
            ->when($filters->storeId !== null, fn (Builder $query) => $query->where('store_id', $filters->storeId))
            ->when($filters->paymentMethod !== null, fn (Builder $query) => $query->where('payment_method', $filters->paymentMethod))
            ->when($filters->dateFrom !== null, fn (Builder $query) => $query->whereDate('sale_date', '>=', $filters->dateFrom))
            ->when($filters->dateTo !== null, fn (Builder $query) => $query->whereDate('sale_date', '<=', $filters->dateTo))
            ->when($term !== '', function (Builder $query) use ($term): void {
                $like = '%'.addcslashes($term, '%_\\').'%';
                $query->where(function (Builder $inner) use ($like): void {
                    $inner->where('invoice_number', 'like', $like)
                        ->orWhereHas('items', fn (Builder $items) => $items->where('title', 'like', $like));
                });
            });
    }

    /**
     * @return Builder<QuickSale>
     */
    private function forAuthenticatedTenant(): Builder
    {
        return $this->eloquentBuilder()->where('tenant_id', auth()->user()->tenant_id);
    }
}
