<?php

namespace DA\Inventory\Models\Queries;

use App\Services\AllocatedStores;
use DA\Inventory\DTO\Product\BarcodeLabelFilterDTO;
use DA\Inventory\DTO\Report\MinStockReportFilterDTO;
use DA\Inventory\Enums\MinStockReportSortColumn;
use DA\Inventory\Enums\MinStockStatus;
use DA\Inventory\Services\Decimal;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProductVariantQueries extends BaseQueries
{
    private const CURRENT_STOCK_EXPRESSION = 'COALESCE(stock.current_stock, 0)';

    /**
     * @return LengthAwarePaginator<int, object>
     */
    public function paginateMinStockReport(MinStockReportFilterDTO $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->minStockReportRowsQuery($filters)->paginate($perPage);
    }

    /**
     * @return Collection<int, object>
     */
    public function minStockReportRows(MinStockReportFilterDTO $filters): Collection
    {
        return $this->minStockReportRowsQuery($filters)->get();
    }

    /**
     * @return array{
     *     total_items: int,
     *     out_of_stock_items: int,
     *     low_stock_items: int,
     *     shortage_quantity: string
     * }
     */
    public function minStockReportTotals(MinStockReportFilterDTO $filters): array
    {
        $row = $this->variantsAtMinStockLevel($filters)
            ->selectRaw('COUNT(*) as total_items')
            ->selectRaw('COALESCE(SUM(CASE WHEN '.self::CURRENT_STOCK_EXPRESSION.' <= 0 THEN 1 ELSE 0 END), 0) as out_of_stock_items')
            ->selectRaw('COALESCE(SUM(pv.min_stock_level - '.self::CURRENT_STOCK_EXPRESSION.'), 0) as shortage_quantity')
            ->first();

        $totalItems = (int) ($row?->total_items ?? 0);
        $outOfStockItems = (int) ($row?->out_of_stock_items ?? 0);

        return [
            'total_items' => $totalItems,
            'out_of_stock_items' => $outOfStockItems,
            'low_stock_items' => $totalItems - $outOfStockItems,
            'shortage_quantity' => Decimal::normalize($row?->shortage_quantity ?? 0),
        ];
    }

    /**
     * @param  list<string>  $skus
     * @return Collection<int, string>
     */
    public function takenSkus(array $skus, ?int $ignoreProductId = null): Collection
    {
        return $this->takenCodes('sku', $skus, $ignoreProductId);
    }

    /**
     * @param  list<string>  $barcodes
     * @return Collection<int, string>
     */
    public function takenBarcodes(array $barcodes, ?int $ignoreProductId = null): Collection
    {
        return $this->takenCodes('barcode', $barcodes, $ignoreProductId);
    }

    /**
     * @return LengthAwarePaginator<int, object>
     */
    public function paginateVariantsForBarcodeLabels(BarcodeLabelFilterDTO $filters, int $perPage = 20): LengthAwarePaginator
    {
        $tenantId = auth()->user()->tenant_id;
        $term = trim((string) $filters->search);

        return DB::table('product_variants', 'pv')
            ->join('products as p', 'p.id', '=', 'pv.product_id')
            ->leftJoin('categories as c', 'c.id', '=', 'p.category_id')
            ->where('pv.tenant_id', $tenantId)
            ->where('p.tenant_id', $tenantId)
            ->whereNull('pv.deleted_at')
            ->whereNull('p.deleted_at')
            ->where('pv.is_active', true)
            ->where('p.is_active', true)
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
                        ->orWhere('pv.barcode', 'like', $like)
                        ->orWhere('p.name', 'like', $like)
                        ->orWhere('pv.name', 'like', $like);
                });
            })
            ->select([
                'pv.id as product_variant_id',
                'pv.product_id',
                'p.name as product_name',
                'pv.name as variant_name',
                'pv.sku',
                'pv.barcode',
                'pv.barcode_type',
                'pv.selling_price',
                'pv.image as variant_image',
                'p.image as product_image',
                'c.name as category_name',
            ])
            ->orderBy('p.name')
            ->orderBy('pv.id')
            ->paginate($perPage);
    }

    public function skuExists(string $sku): bool
    {
        return $this->eloquentBuilder()
            ->withTrashed()
            ->where('tenant_id', auth()->user()->tenant_id)
            ->where('sku', $sku)
            ->exists();
    }

    /**
     * Prefix match keeps the lookup on the (tenant_id, sku) and (tenant_id, barcode) unique indexes.
     */
    public function productIdsWithCodeStartingWith(string $term)
    {
        $prefix = addcslashes($term, '%_\\').'%';

        return DB::table('product_variants')
            ->select('product_id')
            ->where('tenant_id', auth()->user()->tenant_id)
            ->where(function ($query) use ($prefix): void {
                $query->where('barcode', 'like', $prefix)
                    ->orWhere('sku', 'like', $prefix);
            });
    }

    public function latestSequentialBarcodeNumber(string $prefix): int
    {
        $barcodes = $this->eloquentBuilder()
            ->withTrashed()
            ->where('tenant_id', auth()->user()->tenant_id)
            ->where('barcode', 'like', $prefix.'%')
            ->pluck('barcode');

        $highest = 0;

        foreach ($barcodes as $barcode) {
            if (! is_string($barcode) || ! preg_match('/^'.preg_quote($prefix, '/').'(\d+)$/', $barcode, $matches)) {
                continue;
            }

            $highest = max($highest, (int) $matches[1]);
        }

        return $highest;
    }

    private function minStockReportRowsQuery(MinStockReportFilterDTO $filters): Builder
    {
        return $this->variantsAtMinStockLevel($filters)
            ->leftJoin('units as u', 'u.id', '=', 'p.unit_id')
            ->select([
                'pv.id as product_variant_id',
                'pv.sku',
                'p.name as product_name',
                'pv.name as variant_name',
                'b.name as brand_name',
                'c.name as category_name',
                'u.code as unit_code',
                'pv.min_stock_level',
            ])
            ->selectRaw(self::CURRENT_STOCK_EXPRESSION.' as current_stock')
            ->selectRaw('pv.min_stock_level - '.self::CURRENT_STOCK_EXPRESSION.' as shortage_quantity')
            ->selectRaw(
                'CASE WHEN '.self::CURRENT_STOCK_EXPRESSION.' <= 0 THEN ? ELSE ? END as stock_status',
                [MinStockStatus::OutOfStock->value, MinStockStatus::LowStock->value],
            )
            ->orderBy($this->minStockReportSortColumn($filters->sortBy), $filters->sortDirection)
            ->orderBy('pv.id');
    }

    /**
     * The minimum level is set per variant, so without a store filter the
     * available stock of every store is summed before it is compared.
     * Variants with no balance row have never been stocked and count as zero.
     */
    private function variantsAtMinStockLevel(MinStockReportFilterDTO $filters): Builder
    {
        $tenantId = auth()->user()->tenant_id;

        $stock = DB::table('inventory_balances')
            ->select('product_variant_id')
            ->selectRaw('SUM(available_quantity) as current_stock')
            ->where('tenant_id', $tenantId)
            ->when(AllocatedStores::ids() !== null, function (Builder $query): void {
                AllocatedStores::constrain($query, 'store_id');
            })
            ->when($filters->storeId !== null, fn (Builder $query) => $query->where('store_id', $filters->storeId))
            ->groupBy('product_variant_id');

        $term = trim((string) $filters->search);

        return DB::table('product_variants', 'pv')
            ->join('products as p', 'p.id', '=', 'pv.product_id')
            ->leftJoin('brands as b', 'b.id', '=', 'p.brand_id')
            ->leftJoin('categories as c', 'c.id', '=', 'p.category_id')
            ->leftJoinSub($stock, 'stock', 'stock.product_variant_id', '=', 'pv.id')
            ->where('pv.tenant_id', $tenantId)
            ->where('p.tenant_id', $tenantId)
            ->whereNull('pv.deleted_at')
            ->whereNull('p.deleted_at')
            ->where('pv.is_active', true)
            ->where('p.is_active', true)
            ->where('p.track_inventory', true)
            ->where('pv.min_stock_level', '>', 0)
            ->whereRaw(self::CURRENT_STOCK_EXPRESSION.' <= pv.min_stock_level')
            ->when($filters->stockStatus === MinStockStatus::OutOfStock, fn (Builder $query) => $query->whereRaw(self::CURRENT_STOCK_EXPRESSION.' <= 0'))
            ->when($filters->stockStatus === MinStockStatus::LowStock, fn (Builder $query) => $query->whereRaw(self::CURRENT_STOCK_EXPRESSION.' > 0'))
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
                        ->orWhere('pv.barcode', 'like', $like)
                        ->orWhere('p.name', 'like', $like)
                        ->orWhere('pv.name', 'like', $like);
                });
            });
    }

    private function minStockReportSortColumn(MinStockReportSortColumn $sortBy): string
    {
        return match ($sortBy) {
            MinStockReportSortColumn::Sku => 'pv.sku',
            MinStockReportSortColumn::ProductName => 'p.name',
            MinStockReportSortColumn::CurrentStock => 'current_stock',
            MinStockReportSortColumn::MinStockLevel => 'pv.min_stock_level',
            MinStockReportSortColumn::ShortageQuantity => 'shortage_quantity',
        };
    }

    /**
     * @param  list<string>  $codes
     * @return Collection<int, string>
     */
    private function takenCodes(string $column, array $codes, ?int $ignoreProductId): Collection
    {
        if ($codes === []) {
            return new Collection;
        }

        return $this->eloquentBuilder()
            ->withTrashed()
            ->where('tenant_id', auth()->user()->tenant_id)
            ->whereIn($column, $codes)
            ->when($ignoreProductId !== null, fn ($query) => $query->where('product_id', '!=', $ignoreProductId))
            ->pluck($column);
    }
}
