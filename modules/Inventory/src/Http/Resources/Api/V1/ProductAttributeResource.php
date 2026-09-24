<?php

namespace DA\Inventory\Http\Resources\Api\V1;

use DA\Inventory\Models\ProductAttribute;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ProductAttribute
 */
class ProductAttributeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'attribute_id' => $this->attribute_id,
            'sort_order' => $this->sort_order,
            'is_required' => $this->is_required,
            'attribute' => AttributeResource::make($this->whenLoaded('attribute')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
