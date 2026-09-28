<?php

namespace DA\Inventory\Models\Queries;

use DA\Inventory\Enums\PurchasePaymentStatus;
use DA\Inventory\Enums\PurchaseStatus;
use DA\Inventory\Models\Purchase;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class PurchaseQueries extends BaseQueries
{
    /**
     * @return LengthAwarePaginator<int, Purchase>
     */
    public function paginateNewestFirst(int $perPage = 15, ?PurchaseStatus $status = null, ?PurchasePaymentStatus $paymentStatus = null): LengthAwarePaginator
    {
        return $this->forAuthenticatedTenant()
            ->with(['supplier', 'store'])
            ->withCount('items')
            ->when($status !== null, fn (Builder $query) => $query->where('status', $status))
            ->when($paymentStatus !== null, fn (Builder $query) => $query->where('payment_status', $paymentStatus))
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function findForTenant(int $id): Purchase
    {
        return $this->forAuthenticatedTenant()->findOrFail($id);
    }

    public function findForDetail(int $id): Purchase
    {
        return $this->forAuthenticatedTenant()
            ->with(['supplier', 'store', 'items.variant'])
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
    private function forAuthenticatedTenant(): Builder
    {
        return $this->eloquentBuilder()->where('tenant_id', auth()->user()->tenant_id);
    }
}
