<?php

namespace App\Actions\User;

use App\DTO\User\UpdateUserDTO;
use App\Models\User;

class UpdateUser
{
    public function handle(User $user, UpdateUserDTO $dto): User
    {
        $user->name = $dto->name;
        $user->email = $dto->email;
        $user->save();

        return $user->fresh(['roles']) ?? $user;
    }
}
