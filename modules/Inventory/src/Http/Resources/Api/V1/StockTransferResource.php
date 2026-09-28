<?php

namespace DA\Inventory\Http\Resources\Api\V1;

use DA\Inventory\Models\StockTransfer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin StockTransfer
 */
class StockTransferResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_variant_id' => $this->product_variant_id,
            'reference_number' => $this->reference_number,
            'from_store_id' => $this->from_store_id,
            'to_store_id' => $this->to_store_id,
            'transfer_date' => $this->transfer_date?->toDateString(),
            'quantity' => $this->quantity,
            'unit_cost' => $this->unit_cost,
            'total_cost' => $this->total_cost,
            'status' => $this->status?->value,
            'notes' => $this->notes,
            'created_by' => $this->created_by,
            'from_store' => StoreResource::make($this->whenLoaded('fromStore')),
            'to_store' => StoreResource::make($this->whenLoaded('toStore')),
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
