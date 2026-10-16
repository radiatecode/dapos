<?php

namespace App\Actions\User;

use App\DTO\User\UpdatePasswordDTO;
use App\Models\User;

class UpdateUserPassword
{
    public function handle(int $id, UpdatePasswordDTO $dto): User
    {
        $user = User::queries()->findById($id);

        $user->password = $dto->password;
        $user->save();

        return $user;
    }
}
