<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\User;

class UserPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->hasPermission(Permission::UsersView);
    }

    public function view(User $actor, User $user): bool
    {
        return $actor->hasPermission(Permission::UsersView);
    }

    public function create(User $actor): bool
    {
        return $actor->hasPermission(Permission::UsersCreate);
    }

    public function update(User $actor, User $user): bool
    {
        return $actor->hasPermission(Permission::UsersUpdate);
    }

    public function delete(User $actor, User $user): bool
    {
        return $actor->hasPermission(Permission::UsersDelete) && $actor->isNot($user);
    }

    public function updatePassword(User $actor, User $user): bool
    {
        return $actor->hasPermission(Permission::UsersUpdate);
    }

    public function updatePhoto(User $actor, User $user): bool
    {
        return $actor->hasPermission(Permission::UsersUpdate);
    }

    public function assignRoles(User $actor, User $user): bool
    {
        return $actor->hasPermission(Permission::UsersUpdate);
    }
}
