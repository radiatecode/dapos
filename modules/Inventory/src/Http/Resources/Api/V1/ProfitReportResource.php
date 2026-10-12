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
 *     sold_quantity: string|float,
 *     net_sales: string|float,
 *     cogs: string|float,
 *     gross_profit: string|float,
 *     gross_margin: string|float
 * } $resource
 */
class ProfitReportResource extends JsonResource
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
            'sold_quantity' => Decimal::normalize($this->resource->sold_quantity),
            'net_sales' => Decimal::normalize($this->resource->net_sales),
            'cogs' => Decimal::normalize($this->resource->cogs),
            'gross_profit' => Decimal::normalize($this->resource->gross_profit),
            'gross_margin' => Decimal::normalize($this->resource->gross_margin),
        ];
    }
}
