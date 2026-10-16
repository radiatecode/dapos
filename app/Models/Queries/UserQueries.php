<?php

namespace App\Models\Queries;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class UserQueries extends BaseQueries
{
    public function findByEmail(string $email): ?User
    {
        return $this->eloquentBuilder()
            ->with('tenant')
            ->where('email', $email)
            ->first();
    }

    public function findWithRole(int $id): ?User
    {
        return $this->eloquentBuilder()
            ->with('role')
            ->findOrFail($id);
    }

    /**
     * @return LengthAwarePaginator<int, User>
     */
    public function paginateNewestFirst(int $perPage = 15): LengthAwarePaginator
    {
        return $this->eloquentBuilder()
            ->with('role')
            ->orderByDesc('id')
            ->paginate($perPage);
    }
}
