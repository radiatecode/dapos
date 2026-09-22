<?php

namespace DA\Inventory\Policies;

use App\Enums\Permission;
use App\Models\User;
use DA\Inventory\Models\AttributeValue;

class AttributeValuePolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->hasPermission(Permission::AttributesView);
    }

    public function view(User $actor, AttributeValue $attributeValue): bool
    {
        return $actor->hasPermission(Permission::AttributesView);
    }

    public function create(User $actor): bool
    {
        return $actor->hasPermission(Permission::AttributesCreate);
    }

    public function update(User $actor, AttributeValue $attributeValue): bool
    {
        return $actor->hasPermission(Permission::AttributesUpdate);
    }

    public function delete(User $actor, AttributeValue $attributeValue): bool
    {
        return $actor->hasPermission(Permission::AttributesDelete);
    }
}
