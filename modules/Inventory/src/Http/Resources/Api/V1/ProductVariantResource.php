<?php

namespace DA\Inventory\Http\Resources\Api\V1;

use DA\Inventory\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * @mixin ProductVariant
 */
class ProductVariantResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'barcode' => $this->barcode,
            'barcode_type' => $this->barcode_type,
            'name' => $this->name,
            'cost_price' => $this->cost_price,
            'selling_price' => $this->selling_price,
            'compare_at_price' => $this->compare_at_price,
            'weight' => $this->weight,
            'quantity' => $this->quantity,
            'min_stock_level' => $this->min_stock_level,
            'image' => $this->image,
            'image_url' => is_string($this->image) && $this->image !== ''
                ? Storage::disk('public')->url($this->image)
                : null,
            'is_default' => $this->is_default,
            'is_active' => $this->is_active,
            'attribute_values' => VariantAttributeValueResource::collection($this->whenLoaded('attributeValues')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
