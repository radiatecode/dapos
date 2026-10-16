<?php

namespace App\Actions\User;

use App\DTO\User\UpdatePasswordDTO;
use App\Models\User;

class UpdateMyPassword
{
    public function handle(UpdatePasswordDTO $dto): User
    {
        $user = User::queries()->findById(auth()->user()->id);

        $user->password = $dto->password;
        $user->save();

        return $user;
    }
}
