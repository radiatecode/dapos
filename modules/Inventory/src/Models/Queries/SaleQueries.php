<?php

namespace DA\Inventory\Models\Queries;

use DA\Inventory\Enums\SalePaymentStatus;
use DA\Inventory\Enums\SaleStatus;
use DA\Inventory\Models\Sale;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class SaleQueries extends BaseQueries
{
    /**
     * @return LengthAwarePaginator<int, Sale>
     */
    public function paginateNewestFirst(
        int $perPage = 15,
        ?SaleStatus $status = null,
        ?SalePaymentStatus $paymentStatus = null,
        ?int $customerId = null,
        ?int $storeId = null,
        ?string $search = null,
        ?string $dateFrom = null,
        ?string $dateTo = null,
    ): LengthAwarePaginator {
        $term = trim((string) $search);

        return $this->forAuthenticatedTenant()
            ->with(['customer', 'store', 'cashier'])
            ->withCount('items')
            ->when($status !== null, fn (Builder $query) => $query->where('status', $status))
            ->when($paymentStatus !== null, fn (Builder $query) => $query->where('payment_status', $paymentStatus))
            ->when($customerId !== null, fn (Builder $query) => $query->where('customer_id', $customerId))
            ->when($storeId !== null, fn (Builder $query) => $query->where('store_id', $storeId))
            ->when($dateFrom !== null, fn (Builder $query) => $query->whereDate('sale_date', '>=', $dateFrom))
            ->when($dateTo !== null, fn (Builder $query) => $query->whereDate('sale_date', '<=', $dateTo))
            ->when($term !== '', function (Builder $query) use ($term): void {
                $like = '%'.addcslashes($term, '%_\\').'%';
                $query->where(function (Builder $inner) use ($like): void {
                    $inner->where('invoice_number', 'like', $like)
                        ->orWhereHas('customer', fn (Builder $customer) => $customer->where('name', 'like', $like));
                });
            })
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function findForDetail(int $id): Sale
    {
        /** @var Sale $sale */
        $sale = $this->forAuthenticatedTenant()
            ->with([
                'customer',
                'store',
                'cashier',
                'items.variant.product',
                'payments.receiver',
            ])
            ->findOrFail($id);

        return $sale;
    }

    public function lockForTenant(int $id): Sale
    {
        /** @var Sale $sale */
        $sale = $this->forAuthenticatedTenant()
            ->whereKey($id)
            ->lockForUpdate()
            ->firstOrFail();

        return $sale;
    }

    public function nextInvoiceNumber(): string
    {
        $base = 'INV-'.now()->format('Ymd');
        $latest = $this->forAuthenticatedTenant()
            ->where('invoice_number', 'like', $base.'-%')
            ->orderByDesc('invoice_number')
            ->lockForUpdate()
            ->first();

        $suffix = 1;

        if ($latest instanceof Sale) {
            $tail = substr((string) $latest->invoice_number, -4);

            if (ctype_digit($tail)) {
                $suffix = ((int) $tail) + 1;
            }
        }

        do {
            $number = $base.'-'.str_pad((string) $suffix, 4, '0', STR_PAD_LEFT);
            $suffix++;
        } while ($this->invoiceNumberExists($number));

        return $number;
    }

    public function invoiceNumberExists(string $invoiceNumber): bool
    {
        return $this->forAuthenticatedTenant()
            ->where('invoice_number', $invoiceNumber)
            ->exists();
    }

    /**
     * @return Builder<Sale>
     */
    private function forAuthenticatedTenant(): Builder
    {
        return $this->eloquentBuilder()->where('tenant_id', auth()->user()->tenant_id);
    }
}
