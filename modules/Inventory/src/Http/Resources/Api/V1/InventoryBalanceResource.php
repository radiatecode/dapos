<?php

namespace DA\Inventory\Http\Resources\Api\V1;

use DA\Inventory\Models\InventoryBalance;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin InventoryBalance
 */
class InventoryBalanceResource extends JsonResource
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
            'quantity' => $this->quantity,
            'reserved_quantity' => $this->reserved_quantity,
            'available_quantity' => $this->available_quantity,
            'stock_value' => $this->stock_value,
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
            'updated_at' => $this->updated_at,
        ];
    }
}
