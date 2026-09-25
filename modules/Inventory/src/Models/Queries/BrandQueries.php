<?php

namespace DA\Inventory\Models\Queries;

use DA\Inventory\Models\Brand;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

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

    public function select2(?string $search = null): Builder
    {
        return $this->eloquentBuilder()
            ->when($search, function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                });
            })
            ->orderBy('name');
    }
}
