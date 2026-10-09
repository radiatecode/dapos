<?php

namespace DA\Inventory\Models\Queries;

use DA\Inventory\Enums\PurchaseReturnRefundStatus;
use DA\Inventory\Enums\PurchaseReturnStatus;
use DA\Inventory\Models\PurchaseReturn;
use DA\Inventory\Services\Decimal;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class PurchaseReturnQueries extends BaseQueries
{
    /**
     * @return LengthAwarePaginator<int, PurchaseReturn>
     */
    public function paginateNewestFirst(
        int $perPage = 15,
        ?PurchaseReturnStatus $status = null,
        ?PurchaseReturnRefundStatus $refundStatus = null,
        ?int $supplierId = null,
        ?int $storeId = null,
        ?int $purchaseId = null,
        ?string $search = null,
        ?string $dateFrom = null,
        ?string $dateTo = null,
    ): LengthAwarePaginator {
        $term = trim((string) $search);

        return $this->forAuthenticatedTenant()
            ->with(['supplier', 'store'])
            ->withCount('items')
            ->when($status !== null, fn (Builder $query) => $query->where('status', $status))
            ->when($refundStatus !== null, fn (Builder $query) => $query->where('refund_status', $refundStatus))
            ->when($supplierId !== null, fn (Builder $query) => $query->where('supplier_id', $supplierId))
            ->when($storeId !== null, fn (Builder $query) => $query->where('store_id', $storeId))
            ->when($purchaseId !== null, fn (Builder $query) => $query->where('purchase_id', $purchaseId))
            ->when($dateFrom !== null, fn (Builder $query) => $query->whereDate('return_date', '>=', $dateFrom))
            ->when($dateTo !== null, fn (Builder $query) => $query->whereDate('return_date', '<=', $dateTo))
            ->when($term !== '', function (Builder $query) use ($term): void {
                $like = '%'.addcslashes($term, '%_\\').'%';
                $query->where(function (Builder $inner) use ($like): void {
                    $inner->where('return_number', 'like', $like)
                        ->orWhere('po_number', 'like', $like);
                });
            })
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    /**
     * @return array{
     *     total_returns: int,
     *     total_amount: string,
     *     draft_count: int,
     *     pending_count: int,
     *     approved_count: int,
     *     reject_count: int,
     *     refund_pending_count: int,
     *     refund_partial_count: int,
     *     refunded_count: int
     * }
     */
    public function summary(): array
    {
        $row = $this->forAuthenticatedTenant()
            ->selectRaw(
                'COUNT(*) as total_returns,
                COALESCE(SUM(grand_total), 0) as total_amount,
                COALESCE(SUM(status = ?), 0) as draft_count,
                COALESCE(SUM(status = ?), 0) as pending_count,
                COALESCE(SUM(status = ?), 0) as approved_count,
                COALESCE(SUM(status = ?), 0) as reject_count,
                COALESCE(SUM(refund_status = ?), 0) as refund_pending_count,
                COALESCE(SUM(refund_status = ?), 0) as refund_partial_count,
                COALESCE(SUM(refund_status = ?), 0) as refunded_count',
                [
                    PurchaseReturnStatus::Draft->value,
                    PurchaseReturnStatus::Pending->value,
                    PurchaseReturnStatus::Approved->value,
                    PurchaseReturnStatus::Reject->value,
                    PurchaseReturnRefundStatus::Pending->value,
                    PurchaseReturnRefundStatus::Partial->value,
                    PurchaseReturnRefundStatus::Refunded->value,
                ],
            )
            ->first();

        return [
            'total_returns' => (int) ($row?->total_returns ?? 0),
            'total_amount' => Decimal::normalize($row?->total_amount ?? 0),
            'draft_count' => (int) ($row?->draft_count ?? 0),
            'pending_count' => (int) ($row?->pending_count ?? 0),
            'approved_count' => (int) ($row?->approved_count ?? 0),
            'reject_count' => (int) ($row?->reject_count ?? 0),
            'refund_pending_count' => (int) ($row?->refund_pending_count ?? 0),
            'refund_partial_count' => (int) ($row?->refund_partial_count ?? 0),
            'refunded_count' => (int) ($row?->refunded_count ?? 0),
        ];
    }

    public function findForTenant(int $id): PurchaseReturn
    {
        return $this->forAuthenticatedTenant()->findOrFail($id);
    }

    public function findForDetail(int $id): PurchaseReturn
    {
        return $this->forAuthenticatedTenant()
            ->with([
                'supplier',
                'store',
                'purchase',
                'creator',
                'items' => fn ($query) => $query->orderBy('id')->with([
                    'variant' => fn ($variant) => $variant->withTrashed()->with('product'),
                    'allocations.layer',
                ]),
            ])
            ->findOrFail($id);
    }

    public function lockForTenant(int $id): PurchaseReturn
    {
        return $this->forAuthenticatedTenant()->whereKey($id)->lockForUpdate()->firstOrFail();
    }

    public function nextReturnNumber(): string
    {
        $base = 'PR-'.now()->format('Ymd');
        $last = $this->forAuthenticatedTenant()
            ->where('return_number', 'like', $base.'-%')
            ->orderByDesc('id')
            ->lockForUpdate()
            ->value('return_number');

        $next = 1;

        if (is_string($last) && preg_match('/(\d+)$/', $last, $matches) === 1) {
            $next = ((int) $matches[1]) + 1;
        }

        return $base.'-'.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    /**
     * @return Builder<PurchaseReturn>
     */
    private function forAuthenticatedTenant(): Builder
    {
        return $this->eloquentBuilder()->where('tenant_id', auth()->user()->tenant_id);
    }
}
