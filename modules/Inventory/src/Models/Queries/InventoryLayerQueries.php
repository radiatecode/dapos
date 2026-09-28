<?php

namespace DA\Inventory\Models\Queries;

use DA\Inventory\Enums\InventoryLayerSourceType;
use DA\Inventory\Enums\InventoryLayerStatus;
use DA\Inventory\Models\InventoryLayer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class InventoryLayerQueries extends BaseQueries
{
    /**
     * @return LengthAwarePaginator<int, InventoryLayer>
     */
    public function paginateForTenant(
        int $perPage = 15,
        ?int $storeId = null,
        ?int $productVariantId = null,
        ?InventoryLayerStatus $status = null,
        ?InventoryLayerSourceType $sourceType = null,
    ): LengthAwarePaginator {
        return $this->forAuthenticatedTenant()
            ->with([
                'store',
                'supplier',
                'variant' => fn ($query) => $query->withTrashed()->with('product'),
            ])
            ->when($storeId !== null, fn (Builder $query) => $query->where('store_id', $storeId))
            ->when($productVariantId !== null, fn (Builder $query) => $query->where('product_variant_id', $productVariantId))
            ->when($status !== null, fn (Builder $query) => $query->where('status', $status))
            ->when($sourceType !== null, fn (Builder $query) => $query->where('source_type', $sourceType))
            ->orderByDesc('received_at')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function findPosted(
        int $tenantId,
        InventoryLayerSourceType $sourceType,
        int $sourceId,
        ?int $sourceItemId,
        int $storeId,
        int $productVariantId,
    ): ?InventoryLayer {
        return $this->eloquentBuilder()
            ->where('tenant_id', $tenantId)
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->where('store_id', $storeId)
            ->where('product_variant_id', $productVariantId)
            ->when(
                $sourceItemId === null,
                fn (Builder $query) => $query->whereNull('source_item_id'),
                fn (Builder $query) => $query->where('source_item_id', $sourceItemId),
            )
            ->first();
    }

    /**
     * @return Collection<int, InventoryLayer>
     */
    public function lockOpenForFifo(int $tenantId, int $storeId, int $productVariantId): Collection
    {
        return $this->eloquentBuilder()
            ->where('tenant_id', $tenantId)
            ->where('store_id', $storeId)
            ->where('product_variant_id', $productVariantId)
            ->where('status', InventoryLayerStatus::Open)
            ->where('remaining_qty', '>', 0)
            ->orderBy('received_at')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();
    }

    /**
     * @return Collection<int, InventoryLayer>
     */
    public function forBalance(int $tenantId, int $storeId, int $productVariantId): Collection
    {
        return $this->eloquentBuilder()
            ->where('tenant_id', $tenantId)
            ->where('store_id', $storeId)
            ->where('product_variant_id', $productVariantId)
            ->get();
    }

    /**
     * @return Collection<int, InventoryLayer>
     */
    public function lockBySource(int $tenantId, InventoryLayerSourceType $sourceType, int $sourceId, int $storeId): Collection
    {
        return $this->eloquentBuilder()
            ->where('tenant_id', $tenantId)
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->where('store_id', $storeId)
            ->orderBy('id')
            ->lockForUpdate()
            ->get();
    }

    public function variantHasLayer(int $tenantId, int $productVariantId): bool
    {
        return $this->eloquentBuilder()
            ->where('tenant_id', $tenantId)
            ->where('product_variant_id', $productVariantId)
            ->exists();
    }

    public function variantHasRemaining(int $tenantId, int $productVariantId): bool
    {
        return $this->eloquentBuilder()
            ->where('tenant_id', $tenantId)
            ->where('product_variant_id', $productVariantId)
            ->where('remaining_qty', '>', 0)
            ->exists();
    }

    /**
     * @param  list<int>  $productVariantIds
     */
    public function anyRemaining(int $tenantId, array $productVariantIds): bool
    {
        if ($productVariantIds === []) {
            return false;
        }

        return $this->eloquentBuilder()
            ->where('tenant_id', $tenantId)
            ->whereIn('product_variant_id', $productVariantIds)
            ->where('remaining_qty', '>', 0)
            ->exists();
    }

    /**
     * @return Builder<InventoryLayer>
     */
    private function forAuthenticatedTenant(): Builder
    {
        return $this->eloquentBuilder()->where('tenant_id', auth()->user()->tenant_id);
    }
}
