<?php

namespace DA\Admin\Actions\Default;

use App\Enums\DefaultRole;
use App\Models\Role;

class CreateDefaultRole
{
    public function handle(int $tenantId, bool $forceUpdate = false)
    {
        $defaultroles = DefaultRole::cases();

        foreach ($defaultroles as $role) {

            if ($forceUpdate) {
                Role::updateOrCreate(
                    ['tenant_id' => $tenantId, 'slug' => $role->value],
                    ['name' => $role->label(), 'permissions' => $role->permissions(), 'is_default' => true]
                );
            } else {
                $role = Role::create([
                    'tenant_id' => $tenantId,
                    'slug' => $role->value,
                    'name' => $role->label(),
                    'permissions' => $role->permissions(),
                    'is_default' => true,
                ]);
            }
        }

        return $role;
    }
}
