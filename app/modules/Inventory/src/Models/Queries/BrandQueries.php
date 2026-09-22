<?php

namespace DA\Inventory\Models\Queries;

use DA\Inventory\Models\Brand;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class BrandQueries extends BaseQueries
{
    /**
     * @return LengthAwarePaginator<int, Brand>
     */
    public function paginateNewestFirst(int $perPage = 15): LengthAwarePaginator
    {
        return $this->eloquentBuilder()
            ->orderByDesc('id')
            ->paginate($perPage);
    }
}
