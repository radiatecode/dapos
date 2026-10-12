<?php

namespace DA\Inventory\Models\Queries;

use DA\Inventory\DTO\Report\SalesReportFilterDTO;
use DA\Inventory\Enums\SalesReportSortColumn;
use DA\Inventory\Enums\SaleStatus;
use DA\Inventory\Services\Decimal;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SaleItemQueries extends BaseQueries
{
    /**
     * Each line receives its share of the invoice grand total and paid amount,
     * so invoice-level discounts and partial payments are reflected per product.
     */
    private const SALES_REPORT_AGGREGATES = '
        ROUND(COALESCE(SUM(lines.quantity), 0), 4) as sold_quantity,
        ROUND(COALESCE(SUM(lines.total * lines.grand_total / NULLIF(lines.sale_lines_total, 0)), 0), 4) as sold_amount,
        ROUND(COALESCE(SUM(lines.total * lines.paid_amount / NULLIF(lines.sale_lines_total, 0)), 0), 4) as paid_amount';

    /**
     * @return LengthAwarePaginator<int, object>
     */
    public function paginateSalesReport(SalesReportFilterDTO $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->salesReportGroupedByVariant($filters)->paginate($perPage);
    }

    /**
     * @return Collection<int, object>
     */
    public function salesReportRows(SalesReportFilterDTO $filters): Collection
    {
        return $this->salesReportGroupedByVariant($filters)->get();
    }

    /**
     * @return array{
     *     total_products: int,
     *     sold_quantity: string,
     *     sold_amount: string,
     *     paid_amount: string,
     *     due_amount: string
     * }
     */
    public function salesReportTotals(SalesReportFilterDTO $filters): array
    {
        $row = $this->salesReportLines($filters)
            ->selectRaw('COUNT(DISTINCT lines.product_variant_id) as total_products,'.self::SALES_REPORT_AGGREGATES)
            ->first();

        $soldAmount = Decimal::normalize($row?->sold_amount ?? 0);
        $paidAmount = Decimal::normalize($row?->paid_amount ?? 0);

        return [
            'total_products' => (int) ($row?->total_products ?? 0),
            'sold_quantity' => Decimal::normalize($row?->sold_quantity ?? 0),
            'sold_amount' => $soldAmount,
            'paid_amount' => $paidAmount,
            'due_amount' => Decimal::sub($soldAmount, $paidAmount),
        ];
    }

    private function salesReportGroupedByVariant(SalesReportFilterDTO $filters): Builder
    {
        return $this->salesReportLines($filters)
            ->select([
                'pv.id as product_variant_id',
                'pv.sku',
                'p.name as product_name',
                'pv.name as variant_name',
                'b.name as brand_name',
                'c.name as category_name',
            ])
            ->selectRaw(self::SALES_REPORT_AGGREGATES)
            ->groupBy('pv.id', 'pv.sku', 'p.name', 'pv.name', 'b.name', 'c.name')
            ->orderBy($this->salesReportSortColumn($filters->sortBy), $filters->sortDirection)
            ->orderBy('pv.id');
    }

    /**
     * Sale lines are windowed per invoice before product filters are applied,
     * so a brand or category filter never distorts an invoice's line total.
     */
    private function salesReportLines(SalesReportFilterDTO $filters): Builder
    {
        $tenantId = auth()->user()->tenant_id;

        $lines = DB::table('sale_items', 'si')
            ->join('sales as s', 's.id', '=', 'si.sale_id')
            ->where('s.tenant_id', $tenantId)
            ->where('si.tenant_id', $tenantId)
            ->where('s.status', SaleStatus::Completed->value)
            ->when($filters->storeId !== null, fn (Builder $query) => $query->where('s.store_id', $filters->storeId))
            ->when($filters->dateFrom !== null, fn (Builder $query) => $query->where('s.sale_date', '>=', $filters->dateFrom))
            ->when($filters->dateTo !== null, fn (Builder $query) => $query->where(
                's.sale_date',
                '<',
                Carbon::parse($filters->dateTo)->addDay()->toDateString(),
            ))
            ->select(['si.product_variant_id', 'si.quantity', 'si.total', 's.grand_total', 's.paid_amount'])
            ->selectRaw('SUM(si.total) OVER (PARTITION BY si.sale_id) as sale_lines_total');

        $term = trim((string) $filters->search);

        return DB::query()
            ->fromSub($lines, 'lines')
            ->join('product_variants as pv', 'pv.id', '=', 'lines.product_variant_id')
            ->join('products as p', 'p.id', '=', 'pv.product_id')
            ->leftJoin('brands as b', 'b.id', '=', 'p.brand_id')
            ->leftJoin('categories as c', 'c.id', '=', 'p.category_id')
            ->when($filters->brandId !== null, fn (Builder $query) => $query->where('p.brand_id', $filters->brandId))
            ->when($filters->categoryId !== null, function (Builder $query) use ($filters): void {
                $query->where(function (Builder $inner) use ($filters): void {
                    $inner->where('p.category_id', $filters->categoryId)
                        ->orWhere('c.parent_id', $filters->categoryId);
                });
            })
            ->when($term !== '', function (Builder $query) use ($term): void {
                $like = '%'.addcslashes($term, '%_\\').'%';
                $query->where(function (Builder $inner) use ($like): void {
                    $inner->where('pv.sku', 'like', $like)
                        ->orWhere('p.name', 'like', $like)
                        ->orWhere('pv.name', 'like', $like);
                });
            });
    }

    private function salesReportSortColumn(SalesReportSortColumn $sortBy): string
    {
        return match ($sortBy) {
            SalesReportSortColumn::Sku => 'pv.sku',
            SalesReportSortColumn::ProductName => 'p.name',
            SalesReportSortColumn::SoldQuantity => 'sold_quantity',
            SalesReportSortColumn::SoldAmount => 'sold_amount',
            SalesReportSortColumn::PaidAmount => 'paid_amount',
        };
    }
}
