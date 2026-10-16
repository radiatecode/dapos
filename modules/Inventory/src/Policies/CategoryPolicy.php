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

        $hasPermission = $actor->hasPermission(Permission::CategoriesUpdate);

        if (! $hasPermission) {
            return false;
        }

        if ($actor->hasPermission(Permission::OnlyCreatorUpdate) && $category->created_by !== $actor->id) {
            return false;
        }

        return true;
    }

    public function delete(User $actor, Category $category): bool
    {
        if ($actor->is_super_admin) {
            return true;
        }

        $hasPermission = $actor->hasPermission(Permission::CategoriesDelete);

        if (! $hasPermission) {
            return false;
        }

        if ($actor->hasPermission(Permission::OnlyCreatorDelete) && $category->created_by !== $actor->id) {
            return false;
        }

        return true;
    }
}
