<?php

namespace DA\Inventory\Services;

use DA\Inventory\Models\Attribute;
use DA\Inventory\Models\AttributeValue;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AttributeValueService
{
    /**
     * @return LengthAwarePaginator<int, AttributeValue>
     */
    public function paginate(Attribute $attribute, int $perPage = 15): LengthAwarePaginator
    {
        return AttributeValue::queries()->paginateForAttribute($attribute->id, $perPage);
    }

    public function show(AttributeValue $attributeValue): AttributeValue
    {
        return $attributeValue;
    }
}
