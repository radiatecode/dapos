<?php

namespace App\Actions\User;

use App\DTO\User\CreateUserDTO;
use App\Models\User;
use DA\Admin\Services\Tenancy\TenantContext;

class CreateUser
{
    public function handle(CreateUserDTO $dto): User
    {
        $user = new User;
        $user->tenant_id = auth()->user()->tenant_id;
        $user->name = $dto->name;
        $user->email = $dto->email;
        $user->password = $dto->password;
        $user->role_id = $dto->role_id;
        $user->save();

        return $user;
    }
}
