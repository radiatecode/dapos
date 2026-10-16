<?php

namespace App\Actions\User;

use App\DTO\User\UpdateUserDTO;
use App\Models\User;

class UpdateUser
{
    public function handle(int $id, UpdateUserDTO $dto): User
    {
        $user = User::queries()->findById($id);

        if ($user->is_super_admin) {
            throw new \Exception('Super admin cannot be updated');
        }

        $user->name = $dto->name;
        $user->email = $dto->email;

        $user->save();

        return $user;
    }
}
