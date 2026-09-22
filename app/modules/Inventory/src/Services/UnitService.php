<?php

namespace DA\Inventory\Services;

use DA\Inventory\Models\Unit;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class UnitService
{
    /**
     * @return LengthAwarePaginator<int, Unit>
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Unit::queries()->paginateNewestFirst($perPage);
    }

    public function show(Unit $unit): Unit
    {
        return $unit;
    }
}
