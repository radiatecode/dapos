<?php

namespace App\Actions\User;

use App\DTO\User\AssignRolesDTO;
use App\Models\User;

class AssignUserRoles
{
    public function handle(User $user, AssignRolesDTO $dto): User
    {
        $user->roles()->sync($dto->role_ids);

        return $user->fresh(['roles']) ?? $user;
    }
}
