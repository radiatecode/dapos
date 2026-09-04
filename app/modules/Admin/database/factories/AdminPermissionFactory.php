<?php

namespace DA\Admin\Database\Factories;

use DA\Admin\Enums\AdminPermission as AdminPermissionEnum;
use DA\Admin\Models\AdminPermission;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AdminPermission>
 */
class AdminPermissionFactory extends Factory
{
    protected $model = AdminPermission::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $permission = fake()->randomElement(AdminPermissionEnum::cases());

        return [
            'name' => $permission->label(),
            'key' => $permission->value.'-'.fake()->unique()->numerify('###'),
            'group' => $permission->group(),
        ];
    }
}
