<?php

namespace DA\Inventory\Models\Queries;

use DA\Inventory\Models\InventoryBalance;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\UniqueConstraintViolationException;

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
     * @return Builder<InventoryBalance>
     */
    private function forAuthenticatedTenant(): Builder
    {
        return $this->eloquentBuilder()->where('tenant_id', auth()->user()->tenant_id);
    }
}
