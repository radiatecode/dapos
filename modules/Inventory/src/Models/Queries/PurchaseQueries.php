<?php

namespace DA\Inventory\Models\Queries;

use DA\Inventory\Enums\PurchasePaymentStatus;
use DA\Inventory\Enums\PurchaseStatus;
use DA\Inventory\Models\Purchase;
use DA\Inventory\Services\Decimal;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class PurchaseQueries extends BaseQueries
{
    /**
     * @return LengthAwarePaginator<int, Purchase>
     */
    public function paginateNewestFirst(
        int $perPage = 15,
        ?PurchaseStatus $status = null,
        ?PurchasePaymentStatus $paymentStatus = null,
        ?int $supplierId = null,
        ?string $search = null,
        ?string $dateFrom = null,
        ?string $dateTo = null,
    ): LengthAwarePaginator {
        return $this->filtered($status, $paymentStatus, $supplierId, $search, $dateFrom, $dateTo)
            ->with(['supplier', 'store'])
            ->withCount('items')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    /**
     * @return array{
     *     total_purchases: int,
     *     total_amount: string,
     *     currency: string|null,
     *     received_count: int,
     *     pending_count: int,
     *     ordered_count: int,
     *     paid_count: int,
     *     unpaid_count: int,
     *     partial_count: int
     * }
     */
    public function summary(): array
    {
        $row = $this->forAuthenticatedTenant()
            ->selectRaw(
                'COUNT(*) as total_purchases,
                COALESCE(SUM(grand_total), 0) as total_amount,
                MIN(currency) as currency_min,
                MAX(currency) as currency_max,
                COALESCE(SUM(status = ?), 0) as received_count,
                COALESCE(SUM(status = ?), 0) as pending_count,
                COALESCE(SUM(status = ?), 0) as ordered_count,
                COALESCE(SUM(payment_status = ?), 0) as paid_count,
                COALESCE(SUM(payment_status = ?), 0) as unpaid_count,
                COALESCE(SUM(payment_status = ?), 0) as partial_count',
                [
                    PurchaseStatus::Received->value,
                    PurchaseStatus::Pending->value,
                    PurchaseStatus::Ordered->value,
                    PurchasePaymentStatus::Paid->value,
                    PurchasePaymentStatus::Unpaid->value,
                    PurchasePaymentStatus::Partial->value,
                ],
            )
            ->first();

        $currencyMin = $row?->currency_min;
        $currencyMax = $row?->currency_max;

        return [
            'total_purchases' => (int) ($row?->total_purchases ?? 0),
            'total_amount' => Decimal::normalize($row?->total_amount ?? 0),
            'currency' => $currencyMin !== null && $currencyMin === $currencyMax ? $currencyMin : null,
            'received_count' => (int) ($row?->received_count ?? 0),
            'pending_count' => (int) ($row?->pending_count ?? 0),
            'ordered_count' => (int) ($row?->ordered_count ?? 0),
            'paid_count' => (int) ($row?->paid_count ?? 0),
            'unpaid_count' => (int) ($row?->unpaid_count ?? 0),
            'partial_count' => (int) ($row?->partial_count ?? 0),
        ];
    }

    public function findForTenant(int $id): Purchase
    {
        return $this->forAuthenticatedTenant()->findOrFail($id);
    }

    public function findForDetail(int $id): Purchase
    {
        return $this->forAuthenticatedTenant()
            ->with(['supplier', 'store', 'creator', 'items.variant.product'])
            ->findOrFail($id);
    }

    public function poNumberExists(string $poNumber, ?int $ignoreId = null): bool
    {
        return $this->forAuthenticatedTenant()
            ->where('po_number', $poNumber)
            ->when($ignoreId !== null, fn (Builder $query) => $query->where('id', '!=', $ignoreId))
            ->exists();
    }

    public function nextPoNumber(): string
    {
        $base = 'PO-'.now()->format('Ymd');
        $suffix = $this->forAuthenticatedTenant()
            ->where('po_number', 'like', $base.'-%')
            ->count() + 1;

        do {
            $number = $base.'-'.str_pad((string) $suffix, 4, '0', STR_PAD_LEFT);
            $suffix++;
        } while ($this->poNumberExists($number));

        return $number;
    }

    /**
     * @return Builder<Purchase>
     */
    private function filtered(
        ?PurchaseStatus $status,
        ?PurchasePaymentStatus $paymentStatus,
        ?int $supplierId,
        ?string $search,
        ?string $dateFrom,
        ?string $dateTo,
    ): Builder {
        $term = trim((string) $search);

        return $this->forAuthenticatedTenant()
            ->when($status !== null, fn (Builder $query) => $query->where('status', $status))
            ->when($paymentStatus !== null, fn (Builder $query) => $query->where('payment_status', $paymentStatus))
            ->when($supplierId !== null, fn (Builder $query) => $query->where('supplier_id', $supplierId))
            ->when($dateFrom !== null, fn (Builder $query) => $query->whereDate('order_date', '>=', $dateFrom))
            ->when($dateTo !== null, fn (Builder $query) => $query->whereDate('order_date', '<=', $dateTo))
            ->when($term !== '', function (Builder $query) use ($term): void {
                $like = '%'.addcslashes($term, '%_\\').'%';
                $query->where(function (Builder $inner) use ($like): void {
                    $inner->where('po_number', 'like', $like)
                        ->orWhereHas('supplier', fn (Builder $supplier) => $supplier->where('name', 'like', $like));
                });
            });
    }

    /**
     * @return Builder<Purchase>
     */
    private function forAuthenticatedTenant(): Builder
    {
        return $this->eloquentBuilder()->where('tenant_id', auth()->user()->tenant_id);
    }
}
