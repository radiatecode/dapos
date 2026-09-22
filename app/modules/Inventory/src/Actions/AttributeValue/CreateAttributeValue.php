<?php

namespace DA\Inventory\Actions\AttributeValue;

use DA\Inventory\DTO\AttributeValue\AttributeValueDTO;
use DA\Inventory\Models\Attribute;
use DA\Inventory\Models\AttributeValue;

class CreateAttributeValue
{
    public function handle(Attribute $attribute, AttributeValueDTO $dto): AttributeValue
    {
        $value = new AttributeValue;
        $value->attribute_id = $attribute->id;
        $value->value = $dto->value;
        $value->slug = $dto->slug ?: AttributeValue::queries()->uniqueSlugFromForAttribute(
            $attribute->id,
            $dto->value,
            'value',
        );
        $value->code = $dto->code;
        $value->sort_order = $dto->sortOrder;
        $value->is_active = $dto->isActive;
        $value->save();

        return $value;
    }
}
