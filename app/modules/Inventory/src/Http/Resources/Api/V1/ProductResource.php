<?php

namespace DA\Inventory\Http\Resources\Api\V1;

use DA\Inventory\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * @mixin Product
 */
class ProductResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'category_id' => $this->category_id,
            'brand_id' => $this->brand_id,
            'unit_id' => $this->unit_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'image' => $this->image,
            'image_url' => is_string($this->image) && $this->image !== ''
                ? Storage::disk('public')->url($this->image)
                : null,
            'product_type' => $this->product_type,
            'track_inventory' => $this->track_inventory,
            'is_active' => $this->is_active,
            'is_stock_out' => $this->is_stock_out,
            'manufacture_date' => $this->manufacture_date?->toDateString(),
            'expire_date' => $this->expire_date?->toDateString(),
            'warranty_in_days' => $this->warranty_in_days,
            'guarantee_in_days' => $this->guarantee_in_days,
            'category' => CategoryResource::make($this->whenLoaded('category')),
            'brand' => BrandResource::make($this->whenLoaded('brand')),
            'unit' => UnitResource::make($this->whenLoaded('unit')),
            'attributes' => ProductAttributeResource::collection($this->whenLoaded('productAttributes')),
            'variants' => ProductVariantResource::collection($this->whenLoaded('variants')),
            'default_variant' => $this->when(
                $this->relationLoaded('defaultVariant') && $this->defaultVariant !== null,
                fn () => ProductVariantResource::make($this->defaultVariant),
            ),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
