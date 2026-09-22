<?php

namespace DA\Inventory\Services;

use DA\Inventory\Models\Attribute;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AttributeService
{
    /**
     * @return LengthAwarePaginator<int, Attribute>
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Attribute::queries()->paginateBySortOrder($perPage);
    }

    public function show(Attribute $attribute): Attribute
    {
        return $attribute->load('values');
    }
}
