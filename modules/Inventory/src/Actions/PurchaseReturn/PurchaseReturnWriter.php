<?php

namespace DA\Inventory\Actions\PurchaseReturn;

use DA\Inventory\DTO\PurchaseReturn\PurchaseReturnDTO;
use DA\Inventory\Enums\PurchaseReturnRefundStatus;
use DA\Inventory\Models\PurchaseReturn;
use DA\Inventory\Models\PurchaseReturnItem;
use DA\Inventory\Services\PurchaseReturnTotals;
use Illuminate\Validation\ValidationException;

class PurchaseReturnWriter
{
    public function __construct(
        private AssertPurchaseReturn $assert,
        private PurchaseReturnTotals $totals,
    ) {}

    /**
     * @return list<array{subtotal: string, total_amount: string}>
     */
    public function fill(PurchaseReturn $return, PurchaseReturnDTO $dto, bool $creating): array
    {
        if (! $dto->status->isOpen()) {
            throw ValidationException::withMessages([
                'status' => 'The selected status is invalid.',
            ]);
        }

        $purchase = $this->assert->handle($dto)['purchase'];
        $totals = $this->totals->calculate($dto);
        $userId = auth()->id();

        $return->tenant_id = auth()->user()->tenant_id;
        $return->store_id = $purchase->store_id;
        $return->supplier_id = $purchase->supplier_id;
        $return->purchase_id = $purchase->id;
        $return->po_number = $purchase->po_number;
        $return->return_date = $dto->returnDate;
        $return->total_return_qty = $totals['total_return_qty'];
        $return->subtotal = $totals['subtotal'];
        $return->discount_amount = $dto->discountAmount;
        $return->tax_amount = $dto->taxAmount;
        $return->grand_total = $totals['grand_total'];
        $return->reason = $dto->reason;
        $return->notes = $dto->notes;
        $return->status = $dto->status;
        $return->updated_by = $userId;

        if ($creating) {
            $return->return_number = PurchaseReturn::queries()->nextReturnNumber();
            $return->refund_status = PurchaseReturnRefundStatus::Pending;
            $return->created_by = $userId;
        }

        return $totals['lines'];
    }

    /**
     * @param  list<array{subtotal: string, total_amount: string}>  $lineTotals
     */
    public function syncItems(PurchaseReturn $return, PurchaseReturnDTO $dto, array $lineTotals): void
    {
        $existing = $return->items()->get()->keyBy('id');
        $retainedIds = [];

        foreach ($dto->items as $index => $item) {
            $row = ($item->id !== null && $existing->has($item->id))
                ? $existing->get($item->id)
                : new PurchaseReturnItem;

            if (! $row->exists) {
                $row->purchase_return_id = $return->id;
                $row->tenant_id = $return->tenant_id;
            }

            $row->purchase_order_item_id = $item->purchaseOrderItemId;
            $row->product_variant_id = $item->productVariantId;
            $row->quantity = $item->quantity;
            $row->unit_cost = $item->unitCost;
            $row->discount_amount = $item->discountAmount;
            $row->tax_amount = $item->taxAmount;
            $row->subtotal = $lineTotals[$index]['subtotal'];
            $row->total_amount = $lineTotals[$index]['total_amount'];
            $row->save();

            $retainedIds[] = $row->id;
        }

        $return->items()->whereNotIn('id', $retainedIds)->delete();
    }
}
