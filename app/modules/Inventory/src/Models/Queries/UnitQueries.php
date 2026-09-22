<?php

namespace DA\Inventory\Models\Queries;

use DA\Inventory\Models\Unit;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class UnitQueries extends BaseQueries
{
    /**
     * @return LengthAwarePaginator<int, Unit>
     */
    public function paginateNewestFirst(int $perPage = 15): LengthAwarePaginator
    {
        return $this->eloquentBuilder()
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function codeExists(string $code, ?int $ignoreId = null): bool
    {
        return $this->eloquentBuilder()
            ->where('code', $code)
            ->when($ignoreId !== null, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists();
    }
}
