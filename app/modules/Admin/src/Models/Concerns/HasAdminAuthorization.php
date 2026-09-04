<?php

namespace DA\Admin\Models\Concerns;

use DA\Admin\Enums\AdminPermission as AdminPermissionEnum;
use DA\Admin\Models\AdminRole;
use DA\Admin\Models\AdminUser;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;

/**
 * @mixin AdminUser
 */
trait HasAdminAuthorization
{
    /**
     * @return BelongsToMany<AdminRole, $this>
     */
    public function adminRoles(): BelongsToMany
    {
        return $this->belongsToMany(AdminRole::class, 'admin_role_user');
    }

    public function hasAdminPermission(AdminPermissionEnum|string $permission): bool
    {
        $key = $permission instanceof AdminPermissionEnum ? $permission->value : $permission;

        return $this->adminPermissionKeys()->contains($key);
    }

    /**
     * @return Collection<int, string>
     */
    public function adminPermissionKeys(): Collection
    {
        $this->loadMissing('adminRoles.permissions');

        return $this->adminRoles
            ->flatMap(fn (AdminRole $role) => $role->permissions->pluck('key'))
            ->unique()
            ->values();
    }
}
