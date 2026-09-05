<?php

namespace Database\Seeders;

use DA\Admin\Database\Seeders\AdminAuthorizationSeeder;
use DA\Admin\Database\Seeders\CatalogSeeder;
use DA\Admin\Models\AdminRole;
use DA\Admin\Models\AdminUser;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            AdminAuthorizationSeeder::class,
            CatalogSeeder::class,
        ]);

        $admin = AdminUser::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $superAdminRoleId = AdminRole::query()->where('slug', 'super-admin')->value('id');

        if ($superAdminRoleId !== null) {
            $admin->adminRoles()->sync([$superAdminRoleId]);
        }
    }
}
