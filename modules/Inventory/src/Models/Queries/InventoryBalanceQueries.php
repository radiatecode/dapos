<?php

namespace DA\Inventory\Models\Queries;

use DA\Inventory\DTO\Report\CurrentStockValueReportFilterDTO;
use DA\Inventory\Enums\CurrentStockAvailability;
use DA\Inventory\Enums\CurrentStockValueReportSortColumn;
use DA\Inventory\Models\InventoryBalance;
use DA\Inventory\Services\Decimal;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class InventoryBalanceQueries extends BaseQueries
{
    /**
     * @return LengthAwarePaginator<int, InventoryBalance>
     */
    public function paginateForTenant(
        int $perPage = 15,
        ?int $storeId = null,
        ?int $productId = null,
        ?int $productVariantId = null,
    ): LengthAwarePaginator {
        return $this->forAuthenticatedTenant()
            ->with([
                'store',
                'variant' => fn ($query) => $query->withTrashed()->with('product'),
            ])
            ->when($storeId !== null, fn (Builder $query) => $query->where('store_id', $storeId))
            ->when($productVariantId !== null, fn (Builder $query) => $query->where('product_variant_id', $productVariantId))
            ->when($productId !== null, function (Builder $query) use ($productId): void {
                $query->whereHas('variant', function (Builder $variantQuery) use ($productId): void {
                    $variantQuery->withTrashed()->where('product_id', $productId);
                });
            })
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function lockForStoreVariant(int $tenantId, int $storeId, int $productVariantId): InventoryBalance
    {
        $balance = $this->eloquentBuilder()
            ->where('tenant_id', $tenantId)
            ->where('store_id', $storeId)
            ->where('product_variant_id', $productVariantId)
            ->lockForUpdate()
            ->first();

        if ($balance instanceof InventoryBalance) {
            return $balance;
        }

        try {
            $balance = new InventoryBalance;
            $balance->tenant_id = $tenantId;
            $balance->store_id = $storeId;
            $balance->product_variant_id = $productVariantId;
            $balance->quantity = '0.0000';
            $balance->reserved_quantity = '0.0000';
            $balance->available_quantity = '0.0000';
            $balance->stock_value = '0.0000';
            $balance->save();
        } catch (UniqueConstraintViolationException) {
            $balance = $this->eloquentBuilder()
                ->where('tenant_id', $tenantId)
                ->where('store_id', $storeId)
                ->where('product_variant_id', $productVariantId)
                ->firstOrFail();
        }

        return $this->eloquentBuilder()
            ->whereKey($balance->id)
            ->lockForUpdate()
            ->firstOrFail();
    }

    /**
     * @return LengthAwarePaginator<int, object>
     */
    public function paginateCurrentStockValueReport(CurrentStockValueReportFilterDTO $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->currentStockValueReportRowsQuery($filters)->paginate($perPage);
    }

    /**
     * @return Collection<int, object>
     */
    public function currentStockValueReportRows(CurrentStockValueReportFilterDTO $filters): Collection
    {
        return $this->currentStockValueReportRowsQuery($filters)->get();
    }

    /**
     * @return array{
     *     available_products: int,
     *     total_available_quantity: string,
     *     total_on_hand_quantity: string,
     *     total_stock_value: string
     * }
     */
    public function currentStockValueReportTotals(CurrentStockValueReportFilterDTO $filters): array
    {
        $row = $this->stockedVariants($filters)
            ->selectRaw('COALESCE(SUM(CASE WHEN stock.available_quantity > 0 THEN 1 ELSE 0 END), 0) as available_products')
            ->selectRaw('COALESCE(SUM(stock.available_quantity), 0) as total_available_quantity')
            ->selectRaw('COALESCE(SUM(stock.on_hand_quantity), 0) as total_on_hand_quantity')
            ->selectRaw('COALESCE(SUM(stock.stock_value), 0) as total_stock_value')
            ->first();

        return [
            'available_products' => (int) ($row?->available_products ?? 0),
            'total_available_quantity' => Decimal::normalize($row?->total_available_quantity ?? 0),
            'total_on_hand_quantity' => Decimal::normalize($row?->total_on_hand_quantity ?? 0),
            'total_stock_value' => Decimal::normalize($row?->total_stock_value ?? 0),
        ];
    }

    private function currentStockValueReportRowsQuery(CurrentStockValueReportFilterDTO $filters): QueryBuilder
    {
        return $this->stockedVariants($filters)
            ->select([
                'pv.id as product_variant_id',
                'pv.sku',
                'p.name as product_name',
                'pv.name as variant_name',
                'b.name as brand_name',
                'c.name as category_name',
                'u.code as unit_code',
                'stock.on_hand_quantity',
                'stock.available_quantity',
                'stock.stock_value',
            ])
            ->selectRaw('CASE WHEN stock.on_hand_quantity = 0 THEN 0 ELSE stock.stock_value / stock.on_hand_quantity END as unit_cost')
            ->orderBy($this->currentStockValueReportSortColumn($filters->sortBy), $filters->sortDirection)
            ->orderBy('pv.id');
    }

    /**
     * Stock value is the on-hand value stored on each balance (remaining
     * quantity × layer cost). Available quantity is what can still be sold
     * after reservations. A variant with no on-hand quantity is omitted
     * because it adds nothing to the current stock value. Inactive products
     * that still have stock stay in the report so their value is not hidden.
     */
    private function stockedVariants(CurrentStockValueReportFilterDTO $filters): QueryBuilder
    {
        $tenantId = auth()->user()->tenant_id;

        $stock = DB::table('inventory_balances')
            ->select('product_variant_id')
            ->selectRaw('SUM(quantity) as on_hand_quantity')
            ->selectRaw('SUM(available_quantity) as available_quantity')
            ->selectRaw('SUM(stock_value) as stock_value')
            ->where('tenant_id', $tenantId)
            ->when($filters->storeId !== null, fn (QueryBuilder $query) => $query->where('store_id', $filters->storeId))
            ->groupBy('product_variant_id')
            ->havingRaw('SUM(quantity) > 0');

        $term = trim((string) $filters->search);

        return DB::table('product_variants', 'pv')
            ->join('products as p', 'p.id', '=', 'pv.product_id')
            ->leftJoin('brands as b', 'b.id', '=', 'p.brand_id')
            ->leftJoin('categories as c', 'c.id', '=', 'p.category_id')
            ->leftJoin('units as u', 'u.id', '=', 'p.unit_id')
            ->joinSub($stock, 'stock', 'stock.product_variant_id', '=', 'pv.id')
            ->where('pv.tenant_id', $tenantId)
            ->where('p.tenant_id', $tenantId)
            ->whereNull('pv.deleted_at')
            ->whereNull('p.deleted_at')
            ->when(
                $filters->availability === CurrentStockAvailability::Available,
                fn (QueryBuilder $query) => $query->where('stock.available_quantity', '>', 0),
            )
            ->when(
                $filters->availability === CurrentStockAvailability::Reserved,
                fn (QueryBuilder $query) => $query->where('stock.available_quantity', '<=', 0),
            )
            ->when($filters->brandId !== null, fn (QueryBuilder $query) => $query->where('p.brand_id', $filters->brandId))
            ->when($filters->categoryId !== null, function (QueryBuilder $query) use ($filters): void {
                $query->where(function (QueryBuilder $inner) use ($filters): void {
                    $inner->where('p.category_id', $filters->categoryId)
                        ->orWhere('c.parent_id', $filters->categoryId);
                });
            })
            ->when($term !== '', function (QueryBuilder $query) use ($term): void {
                $like = '%'.addcslashes($term, '%_\\').'%';
                $query->where(function (QueryBuilder $inner) use ($like): void {
                    $inner->where('pv.sku', 'like', $like)
                        ->orWhere('pv.barcode', 'like', $like)
                        ->orWhere('p.name', 'like', $like)
                        ->orWhere('pv.name', 'like', $like);
                });
            });
    }

    private function currentStockValueReportSortColumn(CurrentStockValueReportSortColumn $sortBy): string
    {
        return match ($sortBy) {
            CurrentStockValueReportSortColumn::Sku => 'pv.sku',
            CurrentStockValueReportSortColumn::ProductName => 'p.name',
            CurrentStockValueReportSortColumn::AvailableQuantity => 'stock.available_quantity',
            CurrentStockValueReportSortColumn::OnHandQuantity => 'stock.on_hand_quantity',
            CurrentStockValueReportSortColumn::UnitCost => 'unit_cost',
            CurrentStockValueReportSortColumn::StockValue => 'stock.stock_value',
        };
    }

    /**
     * @return Builder<InventoryBalance>
     */
    private function forAuthenticatedTenant(): Builder
    {
        return $this->eloquentBuilder()->where('tenant_id', auth()->user()->tenant_id);
    }
}
