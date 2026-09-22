<?php

namespace App\Services;

use App\Enums\Permission;
use Illuminate\Support\Collection;

class PermissionService
{
    /**
     * @return Collection<int, array{key: string, name: string, group: string}>
     */
    public function catalog(): Collection
    {
        return collect(Permission::cases())
            ->map(fn (Permission $permission): array => [
                'key' => $permission->value,
                'name' => $permission->label(),
                'group' => $permission->group(),
            ])
            ->values();
    }
}
