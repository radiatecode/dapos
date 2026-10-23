<?php

namespace App\Models\Queries;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

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
            ->with(['role', 'stores'])
            ->findOrFail($id);
    }

    /**
     * @return list<int>
     */
    public function allocatedStoreIds(int $userId, int $tenantId): array
    {
        return DB::table('store_user')
            ->where('user_id', $userId)
            ->where('tenant_id', $tenantId)
            ->orderBy('store_id')
            ->pluck('store_id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();
    }

    /**
     * @return LengthAwarePaginator<int, User>
     */
    public function paginateNewestFirst(int $perPage = 15): LengthAwarePaginator
    {
        return $this->eloquentBuilder()
            ->with(['role', 'stores'])
            ->orderByDesc('id')
            ->paginate($perPage);
    }
}
