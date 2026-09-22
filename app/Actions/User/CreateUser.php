<?php

namespace App\Actions\User;

use App\DTO\User\CreateUserDTO;
use App\Enums\UserRole;
use App\Models\User;
use DA\Admin\Services\Tenancy\TenantContext;

class CreateUser
{
    public function __construct(private TenantContext $tenantContext) {}

    public function handle(CreateUserDTO $dto): User
    {
        $user = new User;
        $user->name = $dto->name;
        $user->email = $dto->email;
        $user->password = $dto->password;
        $user->role = UserRole::TenantUser;
        $user->tenant_id = $this->tenantContext->id();
        $user->save();

        if ($dto->role_ids !== []) {
            $user->roles()->sync($dto->role_ids);
        }

        return $user->load('roles');
    }
}
