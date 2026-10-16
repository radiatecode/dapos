<?php

namespace App\Actions\User;

use App\DTO\User\AssignRolesDTO;
use App\Models\User;

class AssignUserRoles
{
    public function handle(int $id, AssignRolesDTO $dto): User
    {
        $user = User::queries()->findById($id);

        $user->role_id = $dto->role_id;

        $user->save();

        return $user;
    }
}
