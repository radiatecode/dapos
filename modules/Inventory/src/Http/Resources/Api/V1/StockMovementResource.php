<?php

namespace DA\Inventory\Http\Resources\Api\V1;

use DA\Inventory\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin StockMovement
 */
class StockMovementResource extends JsonResource
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
            'movement_type' => $this->movement_type?->value,
            'quantity' => $this->quantity,
            'unit_cost' => $this->unit_cost,
            'reference_type' => $this->reference_type?->value,
            'reference_id' => $this->reference_id,
            'reference_item_id' => $this->reference_item_id,
            'inventory_layer_id' => $this->inventory_layer_id,
            'batch_id' => $this->batch_id,
            'serial_id' => $this->serial_id,
            'direction' => $this->direction?->value,
            'occurred_at' => $this->occurred_at,
            'created_by' => $this->created_by,
            'store' => StoreResource::make($this->whenLoaded('store')),
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
        ];
    }
}
