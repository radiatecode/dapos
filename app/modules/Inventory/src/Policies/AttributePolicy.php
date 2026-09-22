<?php

namespace DA\Inventory\Policies;

use App\Enums\Permission;
use App\Models\User;
use DA\Inventory\Models\Attribute;

class AttributePolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->hasPermission(Permission::AttributesView);
    }

    public function view(User $actor, Attribute $attribute): bool
    {
        return $actor->hasPermission(Permission::AttributesView);
    }

    public function create(User $actor): bool
    {
        return $actor->hasPermission(Permission::AttributesCreate);
    }

    public function update(User $actor, Attribute $attribute): bool
    {
        return $actor->hasPermission(Permission::AttributesUpdate);
    }

    public function delete(User $actor, Attribute $attribute): bool
    {
        return $actor->hasPermission(Permission::AttributesDelete);
    }
}
