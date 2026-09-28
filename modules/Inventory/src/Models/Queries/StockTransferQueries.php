<?php

namespace DA\Inventory\Models\Queries;

use DA\Inventory\Enums\StockTransferStatus;
use DA\Inventory\Models\StockTransfer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class StockTransferQueries extends BaseQueries
{
    /**
     * @return LengthAwarePaginator<int, StockTransfer>
     */
    public function paginateNewestFirst(int $perPage = 15, ?StockTransferStatus $status = null): LengthAwarePaginator
    {
        return $this->forAuthenticatedTenant()
            ->with([
                'fromStore',
                'toStore',
                'variant' => fn ($query) => $query->withTrashed()->with('product'),
            ])
            ->when($status !== null, fn (Builder $query) => $query->where('status', $status))
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function findForTenant(int $id): StockTransfer
    {
        return $this->forAuthenticatedTenant()->findOrFail($id);
    }

    public function findForDetail(int $id): StockTransfer
    {
        return $this->forAuthenticatedTenant()
            ->with([
                'fromStore',
                'toStore',
                'variant' => fn ($query) => $query->withTrashed()->with('product'),
            ])
            ->findOrFail($id);
    }

    public function lockForTenant(int $id): StockTransfer
    {
        return $this->forAuthenticatedTenant()->whereKey($id)->lockForUpdate()->firstOrFail();
    }

    public function nextReferenceNumber(): string
    {
        $last = $this->forAuthenticatedTenant()
            ->where('reference_number', 'like', 'TRF-%')
            ->orderByDesc('id')
            ->lockForUpdate()
            ->value('reference_number');

        $next = 1;

        if (is_string($last) && preg_match('/(\d+)$/', $last, $matches) === 1) {
            $next = ((int) $matches[1]) + 1;
        }

        return 'TRF-'.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }

    /**
     * @return Builder<StockTransfer>
     */
    private function forAuthenticatedTenant(): Builder
    {
        return $this->eloquentBuilder()->where('tenant_id', auth()->user()->tenant_id);
    }
}
