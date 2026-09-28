<?php

namespace DA\Inventory\Http\Resources\Api\V1;

use DA\Inventory\Models\Purchase;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Purchase
 */
class PurchaseResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'supplier_id' => $this->supplier_id,
            'store_id' => $this->store_id,
            'po_number' => $this->po_number,
            'order_date' => $this->order_date?->toDateString(),
            'currency' => $this->currency,
            'subtotal' => $this->subtotal,
            'discount_amount' => $this->discount_amount,
            'tax_amount' => $this->tax_amount,
            'shipping_amount' => $this->shipping_amount,
            'grand_total' => $this->grand_total,
            'status' => $this->status?->value,
            'payment_status' => $this->payment_status?->value,
            'notes' => $this->notes,
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'items_count' => $this->whenCounted('items'),
            'supplier' => SupplierResource::make($this->whenLoaded('supplier')),
            'store' => StoreResource::make($this->whenLoaded('store')),
            'items' => PurchaseOrderItemResource::collection($this->whenLoaded('items')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
