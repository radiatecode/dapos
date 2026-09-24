<?php

namespace DA\Inventory\Models\Queries;

use DA\Inventory\Models\Brand;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class BrandQueries extends BaseQueries
{
    /**
     * @return LengthAwarePaginator<int, Brand>
     */
    public function paginateNewestFirst(int $perPage = 15, ?string $name = null): LengthAwarePaginator
    {
        return $this->eloquentBuilder()
            ->when(filled($name), fn ($query) => $query->where('name', 'like', '%'.$name.'%'))
            ->orderByDesc('id')
            ->paginate($perPage);
    }
}
