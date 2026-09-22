<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class UserService
{
    /**
     * @return LengthAwarePaginator<int, User>
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return User::queries()->paginateNewestFirst($perPage);
    }

    public function show(User $user): User
    {
        return $user->loadMissing('roles');
    }
}
