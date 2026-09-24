<?php

namespace DA\Inventory\Models\Queries;

use DA\Inventory\Models\Attribute;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AttributeQueries extends BaseQueries
{
    /**
     * @return LengthAwarePaginator<int, Attribute>
     */
    public function paginateBySortOrder(int $perPage = 15, ?string $name = null): LengthAwarePaginator
    {
        return $this->eloquentBuilder()
            ->when(filled($name), fn ($query) => $query->where('name', 'like', '%'.$name.'%'))
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate($perPage);
    }
}
