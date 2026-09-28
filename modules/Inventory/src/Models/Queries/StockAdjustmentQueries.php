<?php

namespace DA\Inventory\Models\Queries;

use DA\Inventory\Enums\StockAdjustmentStatus;
use DA\Inventory\Models\StockAdjustment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class StockAdjustmentQueries extends BaseQueries
{
    /**
     * @return LengthAwarePaginator<int, StockAdjustment>
     */
    public function paginateNewestFirst(int $perPage = 15, ?StockAdjustmentStatus $status = null, ?int $storeId = null): LengthAwarePaginator
    {
        return $this->forAuthenticatedTenant()
            ->with([
                'store',
                'variant' => fn ($query) => $query->withTrashed()->with('product'),
            ])
            ->when($status !== null, fn (Builder $query) => $query->where('status', $status))
            ->when($storeId !== null, fn (Builder $query) => $query->where('store_id', $storeId))
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function findForTenant(int $id): StockAdjustment
    {
        return $this->forAuthenticatedTenant()->findOrFail($id);
    }

    public function findForDetail(int $id): StockAdjustment
    {
        return $this->forAuthenticatedTenant()
            ->with([
                'store',
                'variant' => fn ($query) => $query->withTrashed()->with('product'),
            ])
            ->findOrFail($id);
    }

    public function lockForTenant(int $id): StockAdjustment
    {
        return $this->forAuthenticatedTenant()->whereKey($id)->lockForUpdate()->firstOrFail();
    }

    public function nextReferenceNumber(): string
    {
        $last = $this->forAuthenticatedTenant()
            ->where('reference_number', 'like', 'ADJ-%')
            ->orderByDesc('id')
            ->lockForUpdate()
            ->value('reference_number');

        $next = 1;

        if (is_string($last) && preg_match('/(\d+)$/', $last, $matches) === 1) {
            $next = ((int) $matches[1]) + 1;
        }

        return 'ADJ-'.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }

    /**
     * @return Builder<StockAdjustment>
     */
    private function forAuthenticatedTenant(): Builder
    {
        return $this->eloquentBuilder()->where('tenant_id', auth()->user()->tenant_id);
    }
}
