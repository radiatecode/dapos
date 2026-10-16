<?php

namespace App\Models\Concerns;

use App\Enums\Permission;
use App\Models\Role;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

trait HasPermissions
{
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
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
        $role = $this->getRelationValue('role');

        return collect($role?->permissions ?? [])
            ->merge($this->extra_permissions ?? [])
            ->unique()
            ->values();
    }
}
