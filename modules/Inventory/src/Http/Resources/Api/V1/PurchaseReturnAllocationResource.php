<?php

namespace DA\Inventory\Http\Resources\Api\V1;

use DA\Inventory\Models\PurchaseReturnAllocation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin PurchaseReturnAllocation
 */
class PurchaseReturnAllocationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'purchase_return_item_id' => $this->purchase_return_item_id,
            'inventory_layer_id' => $this->inventory_layer_id,
            'product_variant_id' => $this->product_variant_id,
            'quantity' => $this->quantity,
            'unit_cost' => $this->unit_cost,
            'total_cost' => $this->total_cost,
            'layer' => $this->whenLoaded('layer', fn (): array => [
                'id' => $this->layer->id,
                'received_qty' => $this->layer->received_qty,
                'remaining_qty' => $this->layer->remaining_qty,
                'unit_cost' => $this->layer->unit_cost,
            ]),
            'created_at' => $this->created_at,
        ];
    }
}
