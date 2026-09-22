<?php

namespace DA\Inventory\Models\Queries;

use DA\Inventory\Models\Attribute;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AttributeQueries extends BaseQueries
{
    /**
     * @return LengthAwarePaginator<int, Attribute>
     */
    public function paginateBySortOrder(int $perPage = 15): LengthAwarePaginator
    {
        return $this->eloquentBuilder()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate($perPage);
    }
}
