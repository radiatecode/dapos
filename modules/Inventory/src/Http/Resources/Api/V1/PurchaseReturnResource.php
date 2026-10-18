<?php

namespace DA\Inventory\Http\Resources\Api\V1;

use DA\Inventory\Models\PurchaseReturn;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin PurchaseReturn
 */
class PurchaseReturnResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'purchase_id' => $this->purchase_id,
            'supplier_id' => $this->supplier_id,
            'store_id' => $this->store_id,
            'return_number' => $this->return_number,
            'po_number' => $this->po_number,
            'return_date' => $this->return_date?->toDateString(),
            'total_return_qty' => $this->total_return_qty,
            'subtotal' => $this->subtotal,
            'discount_amount' => $this->discount_amount,
            'tax_amount' => $this->tax_amount,
            'grand_total' => $this->grand_total,
            'reason' => $this->reason,
            'notes' => $this->notes,
            'status' => $this->status?->value,
            'refund_status' => $this->refund_status?->value,
            'approval_by' => $this->approval_by,
            'approval_at' => $this->approval_at,
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'creator' => $this->whenLoaded('creator', fn (): ?array => $this->creator === null ? null : [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
                'email' => $this->creator->email,
            ]),
            'items_count' => $this->whenCounted('items'),
            'supplier' => SupplierResource::make($this->whenLoaded('supplier')),
            'store' => StoreResource::make($this->whenLoaded('store')),
            'purchase' => $this->whenLoaded('purchase', fn (): array => [
                'id' => $this->purchase->id,
                'po_number' => $this->purchase->po_number,
                'status' => $this->purchase->status?->value,
            ]),
            'items' => PurchaseReturnItemResource::collection($this->whenLoaded('items')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
