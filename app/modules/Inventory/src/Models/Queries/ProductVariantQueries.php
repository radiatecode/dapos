<?php

namespace DA\Inventory\Models\Queries;

use Illuminate\Support\Collection;

class ProductVariantQueries extends BaseQueries
{
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
