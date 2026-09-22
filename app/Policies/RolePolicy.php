<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Role;
use App\Models\User;

class RolePolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->hasPermission(Permission::RolesView);
    }

    public function view(User $actor, Role $role): bool
    {
        return $actor->hasPermission(Permission::RolesView);
    }

    public function create(User $actor): bool
    {
        return $actor->hasPermission(Permission::RolesCreate);
    }

    public function update(User $actor, Role $role): bool
    {
        return $actor->hasPermission(Permission::RolesUpdate);
    }

    public function delete(User $actor, Role $role): bool
    {
        return $actor->hasPermission(Permission::RolesDelete);
    }
}
