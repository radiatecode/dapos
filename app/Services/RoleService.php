<?php

namespace App\Services;

use App\Models\Role;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class RoleService
{
    /**
     * @return LengthAwarePaginator<int, Role>
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Role::queries()->paginateNewestFirst($perPage);
    }

    public function show(int $id): Role
    {
        return Role::queries()->findById($id);
    }
}
