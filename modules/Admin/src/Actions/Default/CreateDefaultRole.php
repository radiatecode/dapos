<?php

namespace DA\Admin\Actions\Default;

use App\Enums\DefaultRole;
use App\Models\Role;

class CreateDefaultRole
{
    public function handle(int $tenantId)
    {
        $defaultroles = DefaultRole::cases();

        foreach ($defaultroles as $role) {

            $role = Role::create([
                'tenant_id' => $tenantId,
                'slug' => $role->value,
                'name' => $role->label(),
                'permissions' => $role->permissions(),
                'is_default' => true,
            ]);
        }

        return $role;
    }
}
