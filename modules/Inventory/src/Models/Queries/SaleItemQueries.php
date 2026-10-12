<?php

namespace DA\Inventory\Models\Queries;

use DA\Inventory\DTO\Report\ProfitReportFilterDTO;
use DA\Inventory\DTO\Report\SalesReportFilterDTO;
use DA\Inventory\DTO\Report\TopSalesReportFilterDTO;
use DA\Inventory\Enums\ProfitReportSortColumn;
use DA\Inventory\Enums\SalesReportSortColumn;
use DA\Inventory\Enums\SaleStatus;
use DA\Inventory\Enums\TopSalesReportSortColumn;
use DA\Inventory\Services\Decimal;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SaleItemQueries extends BaseQueries
{
    /**
     * Each line receives its share of the invoice grand total, so an invoice
     * discount is reflected in the product's sold amount. Paid amount uses the
     * same share of what was collected on that invoice.
     */
    private const SOLD_QUANTITY_EXPRESSION = 'ROUND(COALESCE(SUM(lines.quantity), 0), 4)';

    private const SOLD_AMOUNT_EXPRESSION = 'ROUND(COALESCE(SUM(lines.total * lines.grand_total / NULLIF(lines.sale_lines_total, 0)), 0), 4)';

    private const PAID_AMOUNT_EXPRESSION = 'ROUND(COALESCE(SUM(lines.total * lines.paid_amount / NULLIF(lines.sale_lines_total, 0)), 0), 4)';

    /**
     * Net sales is the line amount after exclusive tax, then the line's share of
     * the invoice grand total, so an invoice discount reduces profit and tax
     * collected for the government does not count as revenue.
     */
    private const NET_SALES_EXPRESSION = 'ROUND(COALESCE(SUM((lines.total - lines.tax_amount) * lines.grand_total / NULLIF(lines.sale_lines_total, 0)), 0), 4)';

    private const COGS_EXPRESSION = 'ROUND(COALESCE(SUM(lines.cogs), 0), 4)';

    private const GROSS_PROFIT_EXPRESSION = 'ROUND('.self::NET_SALES_EXPRESSION.' - '.self::COGS_EXPRESSION.', 4)';

    private const GROSS_MARGIN_EXPRESSION = 'CASE WHEN '.self::NET_SALES_EXPRESSION.' = 0 THEN 0 ELSE ROUND(('.self::GROSS_PROFIT_EXPRESSION.') / '.self::NET_SALES_EXPRESSION.' * 100, 4) END';

    /**
     * @return LengthAwarePaginator<int, object>
     */
    public function paginateSalesReport(SalesReportFilterDTO $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->salesReportGroupedByVariant($filters)->paginate(
            $perPage,
            total: fn (): int => $this->countSoldProducts(
                $this->joinCategory($this->completedSaleLines($filters, withInvoiceShare: false), $filters),
            ),
        );
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
        $row = $this->joinCategory($this->completedSaleLines($filters), $filters)
            ->selectRaw('COUNT(DISTINCT lines.product_variant_id) as total_products, '.$this->salesReportAggregates())
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

    /**
     * @return LengthAwarePaginator<int, object>
     */
    public function paginateTopSalesReport(TopSalesReportFilterDTO $filters, int $perPage = 15): LengthAwarePaginator
    {
        $paginator = $this->topSalesGroupedByVariant($filters)->paginate(
            $perPage,
            total: fn (): int => $this->countSoldProducts($this->completedSaleLines($filters, withInvoiceShare: false)),
        );

        $this->assignRanks(
            $paginator->getCollection(),
            ($paginator->currentPage() - 1) * $paginator->perPage(),
        );

        return $paginator;
    }

    /**
     * @return Collection<int, object>
     */
    public function topSalesRows(TopSalesReportFilterDTO $filters): Collection
    {
        $rows = $this->topSalesGroupedByVariant($filters)->get();

        $this->assignRanks($rows, 0);

        return $rows;
    }

    /**
     * @return array{total_products: int, sold_quantity: string, sold_amount: string}
     */
    public function topSalesTotals(TopSalesReportFilterDTO $filters): array
    {
        $row = $this->completedSaleLines($filters)
            ->selectRaw(
                'COUNT(DISTINCT lines.product_variant_id) as total_products, '
                .self::SOLD_QUANTITY_EXPRESSION.' as sold_quantity, '
                .self::SOLD_AMOUNT_EXPRESSION.' as sold_amount',
            )
            ->first();

        return [
            'total_products' => (int) ($row?->total_products ?? 0),
            'sold_quantity' => Decimal::normalize($row?->sold_quantity ?? 0),
            'sold_amount' => Decimal::normalize($row?->sold_amount ?? 0),
        ];
    }

    /**
     * @return LengthAwarePaginator<int, object>
     */
    public function paginateProfitReport(ProfitReportFilterDTO $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->profitReportGroupedByVariant($filters)->paginate(
            $perPage,
            total: fn (): int => $this->countSoldProducts(
                $this->joinCategory($this->completedSaleLines($filters, withInvoiceShare: false), $filters),
            ),
        );
    }

    /**
     * @return Collection<int, object>
     */
    public function profitReportRows(ProfitReportFilterDTO $filters): Collection
    {
        return $this->profitReportGroupedByVariant($filters)->get();
    }

    /**
     * @return array{
     *     total_products: int,
     *     loss_products: int,
     *     sold_quantity: string,
     *     net_sales: string,
     *     cogs: string,
     *     gross_profit: string,
     *     gross_margin: string
     * }
     */
    public function profitReportTotals(ProfitReportFilterDTO $filters): array
    {
        $row = $this->joinCategory($this->completedSaleLines($filters, withCost: true), $filters)
            ->selectRaw('COUNT(DISTINCT lines.product_variant_id) as total_products, '.$this->profitReportAggregates())
            ->first();

        $lossProducts = DB::query()
            ->fromSub(
                $this->joinCategory($this->completedSaleLines($filters, withCost: true), $filters)
                    ->select('lines.product_variant_id')
                    ->selectRaw(self::GROSS_PROFIT_EXPRESSION.' as gross_profit')
                    ->groupBy('lines.product_variant_id'),
                'profit_rows',
            )
            ->where('gross_profit', '<', 0)
            ->count();

        return [
            'total_products' => (int) ($row?->total_products ?? 0),
            'loss_products' => $lossProducts,
            'sold_quantity' => Decimal::normalize($row?->sold_quantity ?? 0),
            'net_sales' => Decimal::normalize($row?->net_sales ?? 0),
            'cogs' => Decimal::normalize($row?->cogs ?? 0),
            'gross_profit' => Decimal::normalize($row?->gross_profit ?? 0),
            'gross_margin' => Decimal::normalize($row?->gross_margin ?? 0),
        ];
    }

    private function salesReportGroupedByVariant(SalesReportFilterDTO $filters): Builder
    {
        return $this->joinCategory($this->completedSaleLines($filters), $filters)
            ->select($this->productSalesColumns())
            ->selectRaw($this->salesReportAggregates())
            ->groupBy($this->productSalesGroupColumns())
            ->orderBy($this->salesReportSortColumn($filters->sortBy), $filters->sortDirection)
            ->orderBy('pv.id');
    }

    private function topSalesGroupedByVariant(TopSalesReportFilterDTO $filters): Builder
    {
        $query = $this->completedSaleLines($filters)
            ->select($this->productSalesColumns(includeCategory: false))
            ->selectRaw(self::SOLD_QUANTITY_EXPRESSION.' as sold_quantity, '.self::SOLD_AMOUNT_EXPRESSION.' as sold_amount')
            ->groupBy($this->productSalesGroupColumns(includeCategory: false))
            ->orderBy($this->topSalesSortColumn($filters->sortBy), $filters->sortDirection);

        if ($filters->sortBy !== TopSalesReportSortColumn::SoldQuantity) {
            $query->orderByDesc('sold_quantity');
        }

        if ($filters->sortBy !== TopSalesReportSortColumn::SoldAmount) {
            $query->orderByDesc('sold_amount');
        }

        return $query->orderBy('pv.id');
    }

    private function profitReportGroupedByVariant(ProfitReportFilterDTO $filters): Builder
    {
        return $this->joinCategory($this->completedSaleLines($filters, withCost: true), $filters)
            ->select($this->productSalesColumns())
            ->selectRaw($this->profitReportAggregates())
            ->groupBy($this->productSalesGroupColumns())
            ->orderBy($this->profitReportSortColumn($filters->sortBy), $filters->sortDirection)
            ->orderBy('pv.id');
    }

    /**
     * The grouped report rows map one-to-one to variants, so the page total is a
     * distinct variant count that does not need the invoice share window.
     */
    private function countSoldProducts(Builder $lines): int
    {
        return $lines->distinct()->count('lines.product_variant_id');
    }

    /**
     * Sale lines are windowed per invoice before product filters are applied,
     * so a brand or search filter never distorts an invoice's line total.
     * The sales report applies its category filter the same way, after this window.
     *
     * Without the invoice share the derived table has no window function, so
     * MySQL merges it into the outer query instead of materializing it.
     */
    private function completedSaleLines(
        SalesReportFilterDTO|TopSalesReportFilterDTO|ProfitReportFilterDTO $filters,
        bool $withInvoiceShare = true,
        bool $withCost = false,
    ): Builder {
        if ($withCost) {
            $withInvoiceShare = true;
        }

        $tenantId = auth()->user()->tenant_id;

        // get total of all lines for each sale
        // Note: When you could drop it. If the sale flow guaranteed a stored column equal to the sum of the line totals,
        // for example sales.items_total written by CreateSale from the saved lines, you could join sales directly and use that column. The window and the subquery would no longer be needed. That would be a change to the checkout flow and its data, not just to the report query.
        // Or, get the line totals from sale.subtotal + sale.tax_amount = sale_lines_total then get rid of the sub query.
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
            ->when(
                $withInvoiceShare,
                fn (Builder $query) => $query
                    ->select($withCost
                        ? ['si.product_variant_id', 'si.quantity', 'si.total', 'si.tax_amount', 'si.cogs', 's.grand_total', 's.paid_amount']
                        : ['si.product_variant_id', 'si.quantity', 'si.total', 's.grand_total', 's.paid_amount'])
                    ->selectRaw('SUM(si.total) OVER (PARTITION BY si.sale_id) as sale_lines_total'),
                fn (Builder $query) => $query->select('si.product_variant_id'),
            );

        $term = trim((string) $filters->search);

        return DB::query()
            ->fromSub($lines, 'lines')
            ->join('product_variants as pv', 'pv.id', '=', 'lines.product_variant_id')
            ->join('products as p', 'p.id', '=', 'pv.product_id')
            ->leftJoin('brands as b', 'b.id', '=', 'p.brand_id')
            ->where('pv.tenant_id', $tenantId)
            ->where('p.tenant_id', $tenantId)
            ->when($filters->brandId !== null, fn (Builder $query) => $query->where('p.brand_id', $filters->brandId))
            ->when($term !== '', function (Builder $query) use ($term): void {
                $like = '%'.addcslashes($term, '%_\\').'%';
                $query->where(function (Builder $inner) use ($like): void {
                    $inner->where('pv.sku', 'like', $like)
                        ->orWhere('p.name', 'like', $like)
                        ->orWhere('pv.name', 'like', $like);
                });
            });
    }

    /**
     * Category stays on the sales report. The join is applied after the invoice
     * window so a parent-category filter still uses the full invoice line total.
     */
    private function joinCategory(Builder $query, SalesReportFilterDTO|ProfitReportFilterDTO $filters): Builder
    {
        return $query
            ->leftJoin('categories as c', 'c.id', '=', 'p.category_id')
            ->when($filters->categoryId !== null, function (Builder $query) use ($filters): void {
                $query->where(function (Builder $inner) use ($filters): void {
                    $inner->where('p.category_id', $filters->categoryId)
                        ->orWhere('c.parent_id', $filters->categoryId);
                });
            });
    }

    /**
     * @return list<string>
     */
    private function productSalesColumns(bool $includeCategory = true): array
    {
        $columns = [
            'pv.id as product_variant_id',
            'pv.sku',
            'p.name as product_name',
            'pv.name as variant_name',
            'b.name as brand_name',
        ];

        if ($includeCategory) {
            $columns[] = 'c.name as category_name';
        }

        return $columns;
    }

    /**
     * @return list<string>
     */
    private function productSalesGroupColumns(bool $includeCategory = true): array
    {
        $columns = ['pv.id', 'pv.sku', 'p.name', 'pv.name', 'b.name'];

        if ($includeCategory) {
            $columns[] = 'c.name';
        }

        return $columns;
    }

    private function salesReportAggregates(): string
    {
        return self::SOLD_QUANTITY_EXPRESSION.' as sold_quantity, '
            .self::SOLD_AMOUNT_EXPRESSION.' as sold_amount, '
            .self::PAID_AMOUNT_EXPRESSION.' as paid_amount';
    }

    private function profitReportAggregates(): string
    {
        return self::SOLD_QUANTITY_EXPRESSION.' as sold_quantity, '
            .self::NET_SALES_EXPRESSION.' as net_sales, '
            .self::COGS_EXPRESSION.' as cogs, '
            .self::GROSS_PROFIT_EXPRESSION.' as gross_profit, '
            .self::GROSS_MARGIN_EXPRESSION.' as gross_margin';
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

    private function profitReportSortColumn(ProfitReportSortColumn $sortBy): string
    {
        return match ($sortBy) {
            ProfitReportSortColumn::Sku => 'pv.sku',
            ProfitReportSortColumn::ProductName => 'p.name',
            ProfitReportSortColumn::SoldQuantity => 'sold_quantity',
            ProfitReportSortColumn::NetSales => 'net_sales',
            ProfitReportSortColumn::Cogs => 'cogs',
            ProfitReportSortColumn::GrossProfit => 'gross_profit',
            ProfitReportSortColumn::GrossMargin => 'gross_margin',
        };
    }

    private function topSalesSortColumn(TopSalesReportSortColumn $sortBy): string
    {
        return match ($sortBy) {
            TopSalesReportSortColumn::Sku => 'pv.sku',
            TopSalesReportSortColumn::ProductName => 'p.name',
            TopSalesReportSortColumn::BrandName => 'b.name',
            TopSalesReportSortColumn::SoldQuantity => 'sold_quantity',
            TopSalesReportSortColumn::SoldAmount => 'sold_amount',
        };
    }

    /**
     * @param  Collection<int, object>  $rows
     */
    private function assignRanks(Collection $rows, int $offset): void
    {
        $rank = $offset;

        foreach ($rows as $row) {
            $row->rank = ++$rank;
        }
    }
}
