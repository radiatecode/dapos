<?php

namespace App\Models\Queries;

use App\Models\Role;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class RoleQueries extends BaseQueries
{
    /**
     * @return LengthAwarePaginator<int, Role>
     */
    public function paginateNewestFirst(int $perPage = 15): LengthAwarePaginator
    {
        return $this->eloquentBuilder()
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function slugExists(string $slug, ?int $ignoreId = null): bool
    {
        return $this->eloquentBuilder()
            ->where('slug', $slug)
            ->when($ignoreId !== null, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists();
    }
}
