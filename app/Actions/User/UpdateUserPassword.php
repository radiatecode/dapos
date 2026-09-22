<?php

namespace App\Actions\User;

use App\DTO\User\UpdatePasswordDTO;
use App\Models\User;

class UpdateUserPassword
{
    public function handle(User $user, UpdatePasswordDTO $dto): User
    {
        $user->password = $dto->password;
        $user->save();

        return $user->fresh(['roles']) ?? $user;
    }
}
