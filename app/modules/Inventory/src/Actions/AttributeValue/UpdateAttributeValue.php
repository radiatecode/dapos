<?php

namespace DA\Inventory\Actions\AttributeValue;

use DA\Inventory\DTO\AttributeValue\AttributeValueDTO;
use DA\Inventory\Models\AttributeValue;

class UpdateAttributeValue
{
    public function handle(AttributeValue $attributeValue, AttributeValueDTO $dto): AttributeValue
    {
        $attributeValue->value = $dto->value;
        $attributeValue->slug = $dto->slug ?: AttributeValue::queries()->uniqueSlugFromForAttribute(
            $attributeValue->attribute_id,
            $dto->value,
            'value',
            $attributeValue->id,
        );
        $attributeValue->code = $dto->code;
        $attributeValue->sort_order = $dto->sortOrder;
        $attributeValue->is_active = $dto->isActive;
        $attributeValue->save();

        return $attributeValue->refresh();
    }
}
