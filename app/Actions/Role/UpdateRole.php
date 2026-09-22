<?php

namespace App\Actions\Role;

use App\DTO\Role\RoleDTO;
use App\Models\Role;
use Illuminate\Support\Str;

class UpdateRole
{
    public function handle(Role $role, RoleDTO $dto): Role
    {
        $role->name = $dto->name;
        $role->slug = $dto->slug ?: $this->uniqueSlug($dto->name, $role->id);
        $role->permissions = $dto->permissions;
        $role->save();

        return $role->refresh();
    }

    private function uniqueSlug(string $name, int $ignoreId): string
    {
        $base = Str::slug($name) ?: 'role';
        $slug = $base;
        $suffix = 1;

        while (Role::queries()->slugExists($slug, $ignoreId)) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }
}
