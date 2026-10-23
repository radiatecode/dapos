<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Query\Builder;
use WeakMap;

/**
 * Resolves the stores the authenticated user may manage.
 *
 * No rows in store_user means the user manages every store. One or more rows
 * limit the user to those stores. The result is cached on the user instance
 * for the rest of the request.
 */
class AllocatedStores
{
    /**
     * @var WeakMap<User, list<int>|null>|null
     */
    private static ?WeakMap $idsByUser = null;

    /**
     * @return list<int>|null Null when the user is not limited to specific stores.
     */
    public static function ids(): ?array
    {
        $user = auth()->user();

        if (! $user instanceof User || $user->tenant_id === null) {
            return null;
        }

        $cache = self::cache();

        if ($cache->offsetExists($user)) {
            return $cache[$user];
        }

        $ids = User::queries()->allocatedStoreIds((int) $user->id, (int) $user->tenant_id);
        $resolved = $ids === [] ? null : $ids;
        $cache[$user] = $resolved;

        return $resolved;
    }

    public static function allows(int $storeId): bool
    {
        $ids = self::ids();

        return $ids === null || in_array($storeId, $ids, true);
    }

    public static function constrain(Builder $query, string $column): void
    {
        $ids = self::ids();

        if ($ids === null) {
            return;
        }

        $query->whereIn($column, $ids);
    }

    public static function forget(User $user): void
    {
        $cache = self::$idsByUser;

        if ($cache instanceof WeakMap && $cache->offsetExists($user)) {
            unset($cache[$user]);
        }
    }

    /**
     * @return WeakMap<User, list<int>|null>
     */
    private static function cache(): WeakMap
    {
        return self::$idsByUser ??= new WeakMap;
    }
}
