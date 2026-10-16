<?php

namespace Database\Seeders;

use App\Enums\DefaultRole;
use App\Models\Role;
use DA\Admin\Models\Tenant;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaultRoles = DefaultRole::cases();

        foreach ($defaultRoles as $defaultRole) {
            Role::create([
                'tenant_id' => Tenant::first()->id,
                'name' => $defaultRole->label(),
                'slug' => $defaultRole->value,
                'permissions' => $defaultRole->permissions(),
                'is_default' => 1,
            ]);
        }
    }
}
