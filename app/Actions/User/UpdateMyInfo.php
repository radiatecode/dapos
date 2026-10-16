<?php

namespace App\Actions\User;

use App\DTO\User\UpdateUserDTO;
use App\Models\User;

class UpdateMyInfo
{
    public function handle(UpdateUserDTO $dto): User
    {
        $user = User::queries()->findById(auth()->user()->id);

        $user->name = $dto->name;
        $user->email = $dto->email;
        $user->save();

        return $user->fresh();
    }
}
