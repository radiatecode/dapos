<?php

namespace DA\Inventory\Http\Resources\Api\V1;

use DA\Inventory\Models\InventoryLayer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin InventoryLayer
 */
class InventoryLayerResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'product_variant_id' => $this->product_variant_id,
            'supplier_id' => $this->supplier_id,
            'source_type' => $this->source_type?->value,
            'source_id' => $this->source_id,
            'source_item_id' => $this->source_item_id,
            'received_qty' => $this->received_qty,
            'remaining_qty' => $this->remaining_qty,
            'unit_cost' => $this->unit_cost,
            'total_cost' => $this->total_cost,
            'received_at' => $this->received_at,
            'batch_id' => $this->batch_id,
            'expiry_date' => $this->expiry_date?->toDateString(),
            'status' => $this->status?->value,
            'store' => StoreResource::make($this->whenLoaded('store')),
            'supplier' => SupplierResource::make($this->whenLoaded('supplier')),
            'variant' => $this->whenLoaded('variant', fn (): array => [
                'id' => $this->variant->id,
                'sku' => $this->variant->sku,
                'name' => $this->variant->name,
                'product' => $this->variant->relationLoaded('product') && $this->variant->product !== null ? [
                    'id' => $this->variant->product->id,
                    'name' => $this->variant->product->name,
                ] : null,
            ]),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
