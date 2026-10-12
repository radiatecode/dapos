<?php

namespace DA\Inventory\Http\Resources\Api\V1;

use DA\Inventory\Services\Decimal;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read object{
 *     product_variant_id: int,
 *     sku: string,
 *     product_name: string,
 *     variant_name: string|null,
 *     brand_name: string|null,
 *     category_name: string|null,
 *     unit_code: string|null,
 *     on_hand_quantity: string|float,
 *     available_quantity: string|float,
 *     unit_cost: string|float,
 *     stock_value: string|float
 * } $resource
 */
class CurrentStockValueReportResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'product_variant_id' => (int) $this->resource->product_variant_id,
            'sku' => $this->resource->sku,
            'product_name' => $this->resource->product_name,
            'variant_name' => $this->resource->variant_name,
            'brand_name' => $this->resource->brand_name,
            'category_name' => $this->resource->category_name,
            'unit_code' => $this->resource->unit_code,
            'on_hand_quantity' => Decimal::normalize($this->resource->on_hand_quantity),
            'available_quantity' => Decimal::normalize($this->resource->available_quantity),
            'unit_cost' => Decimal::normalize($this->resource->unit_cost),
            'stock_value' => Decimal::normalize($this->resource->stock_value),
        ];
    }
}
