<?php

namespace DA\Inventory\Http\Resources\Api\V1;

use DA\Inventory\Models\StockAdjustment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin StockAdjustment
 */
class StockAdjustmentResource extends JsonResource
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
            'reference_number' => $this->reference_number,
            'adjustment_date' => $this->adjustment_date?->toDateString(),
            'adjustment_type' => $this->adjustment_type?->value,
            'reason' => $this->reason,
            'status' => $this->status?->value,
            'notes' => $this->notes,
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'system_quantity' => $this->system_quantity,
            'adjusted_quantity' => $this->adjusted_quantity,
            'difference_quantity' => $this->difference_quantity,
            'unit_cost' => $this->unit_cost,
            'total_cost' => $this->total_cost,
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
            'updated_at' => $this->updated_at,
        ];
    }
}
