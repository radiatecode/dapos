<?php

namespace App\Models\Concerns;

use App\Enums\Permission;
use App\Models\Role;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;

trait HasPermissions
{
    /**
     * @return BelongsToMany<Role, $this>
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles')->withTimestamps();
    }

    public function hasPermission(Permission|string $permission): bool
    {
        $key = $permission instanceof Permission ? $permission->value : $permission;

        return $this->permissionKeys()->contains($key);
    }

    /**
     * @return Collection<int, string>
     */
    public function permissionKeys(): Collection
    {
        $this->loadMissing('roles');

        return $this->roles
            ->flatMap(fn (Role $role): array => $role->permissions ?? [])
            ->unique()
            ->values();
    }
}
