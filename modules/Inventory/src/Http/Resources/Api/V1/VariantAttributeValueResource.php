<?php

namespace DA\Inventory\Http\Resources\Api\V1;

use DA\Inventory\Models\VariantAttributeValue;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin VariantAttributeValue
 */
class VariantAttributeValueResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'attribute_id' => $this->attribute_id,
            'attribute_value_id' => $this->attribute_value_id,
            'attribute' => AttributeResource::make($this->whenLoaded('attribute')),
            'attribute_value' => AttributeValueResource::make($this->whenLoaded('attributeValue')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
