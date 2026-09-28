<?php

namespace DA\Inventory\Http\Resources\Api\V1;

use DA\Inventory\Models\PurchaseOrderItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin PurchaseOrderItem
 */
class PurchaseOrderItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'purchase_order_id' => $this->purchase_order_id,
            'product_variant_id' => $this->product_variant_id,
            'sku' => $this->whenLoaded('variant', fn () => $this->variant?->sku),
            'quantity' => $this->quantity,
            'unit_cost' => $this->unit_cost,
            'discount_amount' => $this->discount_amount,
            'tax_amount' => $this->tax_amount,
            'total' => $this->total,
        ];
    }
}
