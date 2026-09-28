<?php

namespace DA\Inventory\Models\Queries;

use DA\Inventory\Enums\AllocationSourceType;
use DA\Inventory\Models\InventoryAllocation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class InventoryAllocationQueries extends BaseQueries
{
    /**
     * @return Collection<int, InventoryAllocation>
     */
    public function forSource(
        int $tenantId,
        AllocationSourceType $sourceType,
        int $sourceId,
        ?int $sourceItemId,
    ): Collection {
        return $this->forSourceQuery($tenantId, $sourceType, $sourceId, $sourceItemId)
            ->orderBy('id')
            ->get();
    }

    /**
     * @return Collection<int, InventoryAllocation>
     */
    public function lockForSource(
        int $tenantId,
        AllocationSourceType $sourceType,
        int $sourceId,
        ?int $sourceItemId,
    ): Collection {
        return $this->forSourceQuery($tenantId, $sourceType, $sourceId, $sourceItemId)
            ->orderBy('id')
            ->lockForUpdate()
            ->get();
    }

    /**
     * @return Builder<InventoryAllocation>
     */
    private function forSourceQuery(
        int $tenantId,
        AllocationSourceType $sourceType,
        int $sourceId,
        ?int $sourceItemId,
    ): Builder {
        return $this->eloquentBuilder()
            ->where('tenant_id', $tenantId)
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->when(
                $sourceItemId === null,
                fn (Builder $query) => $query->whereNull('source_item_id'),
                fn (Builder $query) => $query->where('source_item_id', $sourceItemId),
            );
    }
}
