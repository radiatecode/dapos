<?php

namespace DA\Inventory\Policies;

use App\Enums\Permission;
use App\Models\User;
use DA\Inventory\Models\Unit;

class UnitPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->hasPermission(Permission::UnitsView);
    }

    public function view(User $actor, Unit $unit): bool
    {
        return $actor->hasPermission(Permission::UnitsView);
    }

    public function create(User $actor): bool
    {
        return $actor->hasPermission(Permission::UnitsCreate);
    }

    public function update(User $actor, Unit $unit): bool
    {
        return $actor->hasPermission(Permission::UnitsUpdate);
    }

    public function delete(User $actor, Unit $unit): bool
    {
        return $actor->hasPermission(Permission::UnitsDelete);
    }
}
