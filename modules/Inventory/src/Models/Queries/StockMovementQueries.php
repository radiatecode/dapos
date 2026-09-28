<?php

namespace DA\Inventory\Models\Queries;

use DA\Inventory\Enums\StockDirection;
use DA\Inventory\Enums\StockMovementType;
use DA\Inventory\Enums\StockReferenceType;
use DA\Inventory\Models\StockMovement;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class StockMovementQueries extends BaseQueries
{
    /**
     * @return LengthAwarePaginator<int, StockMovement>
     */
    public function paginateForTenant(
        int $perPage = 15,
        ?int $storeId = null,
        ?int $productVariantId = null,
        ?StockMovementType $movementType = null,
        ?StockDirection $direction = null,
        ?string $dateFrom = null,
        ?string $dateTo = null,
    ): LengthAwarePaginator {
        return $this->forAuthenticatedTenant()
            ->with([
                'store',
                'variant' => fn ($query) => $query->withTrashed()->with('product'),
                'layer',
            ])
            ->when($storeId !== null, fn (Builder $query) => $query->where('store_id', $storeId))
            ->when($productVariantId !== null, fn (Builder $query) => $query->where('product_variant_id', $productVariantId))
            ->when($movementType !== null, fn (Builder $query) => $query->where('movement_type', $movementType))
            ->when($direction !== null, fn (Builder $query) => $query->where('direction', $direction))
            ->when($dateFrom !== null, fn (Builder $query) => $query->where('occurred_at', '>=', $dateFrom.' 00:00:00'))
            ->when($dateTo !== null, fn (Builder $query) => $query->where('occurred_at', '<=', $dateTo.' 23:59:59'))
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function findPosted(
        int $tenantId,
        int $storeId,
        StockReferenceType $referenceType,
        int $referenceId,
        ?int $referenceItemId,
        StockDirection $direction,
        int $inventoryLayerId,
    ): ?StockMovement {
        return $this->matchingReference($tenantId, $storeId, $referenceType, $referenceId, $referenceItemId, $direction)
            ->where('inventory_layer_id', $inventoryLayerId)
            ->first();
    }

    /**
     * @return Collection<int, StockMovement>
     */
    public function forReference(
        int $tenantId,
        int $storeId,
        StockReferenceType $referenceType,
        int $referenceId,
        StockDirection $direction,
    ): Collection {
        return $this->eloquentBuilder()
            ->where('tenant_id', $tenantId)
            ->where('store_id', $storeId)
            ->where('reference_type', $referenceType)
            ->where('reference_id', $referenceId)
            ->where('direction', $direction)
            ->orderBy('id')
            ->get();
    }

    /**
     * @return Builder<StockMovement>
     */
    private function matchingReference(
        int $tenantId,
        int $storeId,
        StockReferenceType $referenceType,
        int $referenceId,
        ?int $referenceItemId,
        StockDirection $direction,
    ): Builder {
        return $this->eloquentBuilder()
            ->where('tenant_id', $tenantId)
            ->where('store_id', $storeId)
            ->where('reference_type', $referenceType)
            ->where('reference_id', $referenceId)
            ->where('direction', $direction)
            ->when(
                $referenceItemId === null,
                fn (Builder $query) => $query->whereNull('reference_item_id'),
                fn (Builder $query) => $query->where('reference_item_id', $referenceItemId),
            );
    }

    /**
     * @return Builder<StockMovement>
     */
    private function forAuthenticatedTenant(): Builder
    {
        return $this->eloquentBuilder()->where('tenant_id', auth()->user()->tenant_id);
    }
}
