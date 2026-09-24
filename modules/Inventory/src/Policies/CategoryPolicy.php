<?php

namespace DA\Inventory\Policies;

use App\Enums\Permission;
use App\Models\User;
use DA\Inventory\Models\Category;

class CategoryPolicy
{
    public function viewAny(User $actor): bool
    {
        if ($actor->is_super_admin) {
            return true;
        }

        return $actor->hasPermission(Permission::CategoriesView);
    }

    public function view(User $actor, Category $category): bool
    {
        if ($actor->is_super_admin) {
            return true;
        }

        return $actor->hasPermission(Permission::CategoriesView);
    }

    public function create(User $actor): bool
    {
        if ($actor->is_super_admin) {
            return true;
        }

        return $actor->hasPermission(Permission::CategoriesCreate);
    }

    public function update(User $actor, Category $category): bool
    {
        if ($actor->is_super_admin) {
            return true;
        }

        return $actor->hasPermission(Permission::CategoriesUpdate);
    }

    public function delete(User $actor, Category $category): bool
    {
        if ($actor->is_super_admin) {
            return true;
        }

        return $actor->hasPermission(Permission::CategoriesDelete);
    }
}
