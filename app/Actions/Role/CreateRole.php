<?php

namespace App\Actions\Role;

use App\DTO\Role\RoleDTO;
use App\Models\Role;
use Illuminate\Support\Str;

class CreateRole
{
    public function handle(RoleDTO $dto): Role
    {
        $role = new Role;
        $role->name = $dto->name;
        $role->slug = $dto->slug ?: $this->uniqueSlug($dto->name);
        $role->permissions = $dto->permissions;
        $role->save();

        return $role;
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'role';
        $slug = $base;
        $suffix = 1;

        while (Role::queries()->slugExists($slug)) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }
}
