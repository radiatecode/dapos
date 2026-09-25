<?php

namespace DA\Inventory\Actions\AttributeValue;

use DA\Inventory\DTO\AttributeValue\AttributeValueDTO;
use DA\Inventory\Models\AttributeValue;

class CreateAttributeValue
{
    public function handle(AttributeValueDTO $dto): AttributeValue
    {
        $value = new AttributeValue;

        $value->attribute_id = $dto->attributeId;
        $value->value = $dto->value;
        $value->slug = $dto->slug ?: AttributeValue::queries()->uniqueSlugFromForAttribute(
            $dto->attributeId,
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
