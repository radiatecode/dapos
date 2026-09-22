<?php

namespace App\Actions\Role;

use App\Models\Role;

class DeleteRole
{
    public function handle(Role $role): void
    {
        $role->delete();
    }
}
