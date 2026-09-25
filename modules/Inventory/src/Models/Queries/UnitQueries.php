<?php

namespace DA\Inventory\Models\Queries;

use DA\Inventory\Models\Unit;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

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

    public function codeExists(string $code, ?int $ignoreId = null): bool
    {
        return $this->eloquentBuilder()
            ->where('code', $code)
            ->when($ignoreId !== null, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists();
    }
}
