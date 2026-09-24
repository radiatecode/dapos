<?php

namespace DA\Admin\Database\Seeders;

use DA\Admin\Enums\AdminPermission as AdminPermissionEnum;
use DA\Admin\Models\AdminPermission;
use DA\Admin\Models\AdminRole;
use Illuminate\Database\Seeder;

class AdminAuthorizationSeeder extends Seeder
{
    public function run(): void
    {
        $permissionIds = collect(AdminPermissionEnum::cases())->map(
            fn (AdminPermissionEnum $permission) => AdminPermission::query()->updateOrCreate(
                ['key' => $permission->value],
                [
                    'name' => $permission->label(),
                    'group' => $permission->group(),
                ],
            )->id,
        );

        $role = AdminRole::query()->updateOrCreate(
            ['slug' => 'super-admin'],
            [
                'name' => 'Super Admin',
                'description' => 'Full access to the provider admin panel.',
            ],
        );

        $role->permissions()->sync($permissionIds);
    }
}
