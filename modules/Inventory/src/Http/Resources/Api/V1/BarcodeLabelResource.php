<?php

namespace DA\Inventory\Http\Resources\Api\V1;

use DA\Inventory\Services\Decimal;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * @property-read object{
 *     product_variant_id: int,
 *     product_id: int,
 *     product_name: string,
 *     variant_name: string|null,
 *     sku: string,
 *     barcode: string,
 *     barcode_type: string,
 *     selling_price: string|float|null,
 *     variant_image: string|null,
 *     product_image: string|null,
 *     category_name: string|null
 * } $resource
 */
class BarcodeLabelResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $image = $this->resource->variant_image ?: $this->resource->product_image;

        return [
            'product_variant_id' => (int) $this->resource->product_variant_id,
            'product_id' => (int) $this->resource->product_id,
            'product_name' => $this->resource->product_name,
            'variant_name' => $this->resource->variant_name,
            'sku' => $this->resource->sku,
            'barcode' => $this->resource->barcode,
            'barcode_type' => $this->resource->barcode_type,
            'selling_price' => Decimal::normalize($this->resource->selling_price ?? 0),
            'category_name' => $this->resource->category_name,
            'image_url' => is_string($image) && $image !== ''
                ? Storage::disk('public')->url($image)
                : asset('media/no-image-product.jpg'),
        ];
    }
}
