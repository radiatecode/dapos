<?php

namespace DA\Inventory\Actions\Purchase;

use DA\Inventory\DTO\Purchase\PurchaseDTO;
use DA\Inventory\Models\Purchase;
use DA\Inventory\Models\PurchaseOrderItem;
use DA\Inventory\Services\PurchaseTotals;

class PurchaseWriter
{
    public function __construct(private PurchaseTotals $totals) {}

    /**
     * @return list<string>
     */
    public function fill(Purchase $purchase, PurchaseDTO $dto, bool $creating): array
    {
        $totals = $this->totals->calculate($dto);
        $userId = auth()->id();

        $purchase->tenant_id = auth()->user()->tenant_id;
        $purchase->supplier_id = $dto->supplierId;
        $purchase->store_id = $dto->storeId;
        $purchase->po_number = ($dto->poNumber !== null && $dto->poNumber !== '')
            ? $dto->poNumber
            : Purchase::queries()->nextPoNumber();
        $purchase->order_date = $dto->orderDate;
        $purchase->currency = $dto->currency;
        $purchase->subtotal = $totals['subtotal'];
        $purchase->discount_amount = $dto->discountAmount;
        $purchase->tax_amount = $dto->taxAmount;
        $purchase->shipping_amount = $dto->shippingAmount;
        $purchase->grand_total = $totals['grand_total'];
        $purchase->status = $dto->status;
        $purchase->payment_status = $dto->paymentStatus;
        $purchase->notes = $dto->notes;
        $purchase->updated_by = $userId;

        if ($creating) {
            $purchase->created_by = $userId;
        }

        return $totals['lines'];
    }

    /**
     * @param  list<string>  $lineTotals
     */
    public function syncItems(Purchase $purchase, PurchaseDTO $dto, array $lineTotals): void
    {
        $existing = $purchase->items()->get()->keyBy('id');
        $retainedIds = [];

        foreach ($dto->items as $index => $item) {
            $row = ($item->id !== null && $existing->has($item->id))
                ? $existing->get($item->id)
                : new PurchaseOrderItem;

            if (! $row->exists) {
                $row->purchase_order_id = $purchase->id;
            }

            $row->product_variant_id = $item->productVariantId;
            $row->quantity = $item->quantity;
            $row->unit_cost = $item->unitCost;
            $row->discount_amount = $item->discountAmount;
            $row->tax_amount = $item->taxAmount;
            $row->total = $lineTotals[$index];
            $row->save();

            $retainedIds[] = $row->id;
        }

        $purchase->items()->whereNotIn('id', $retainedIds)->delete();
    }
}
