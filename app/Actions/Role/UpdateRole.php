<?php

namespace App\Actions\Role;

use App\DTO\Role\RoleDTO;
use App\Models\Role;
use Illuminate\Support\Str;

class UpdateRole
{
    public function handle(int $id, RoleDTO $dto): Role
    {
        $role = Role::queries()->findById($id);

        $role->name = $dto->name;
        $role->slug = $dto->slug ?: $this->uniqueSlug($dto->name, $role->id);
        $role->permissions = $dto->permissions;
        $role->save();

        return $role;
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
