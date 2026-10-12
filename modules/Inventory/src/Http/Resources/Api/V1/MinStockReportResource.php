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
 *     current_stock: string|float,
 *     min_stock_level: int|string,
 *     shortage_quantity: string|float,
 *     stock_status: string
 * } $resource
 */
class MinStockReportResource extends JsonResource
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
            'current_stock' => Decimal::normalize($this->resource->current_stock),
            'min_stock_level' => (int) $this->resource->min_stock_level,
            'shortage_quantity' => Decimal::normalize($this->resource->shortage_quantity),
            'stock_status' => $this->resource->stock_status,
        ];
    }
}
