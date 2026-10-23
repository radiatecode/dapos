<?php

namespace App\Actions\User;

use App\DTO\User\CreateUserDTO;
use App\Models\User;
use App\Services\AllocatedStores;
use Illuminate\Support\Facades\DB;

class CreateUser
{
    public function __construct(private SyncUserStores $syncStores) {}

    public function handle(CreateUserDTO $dto): User
    {
        return DB::transaction(function () use ($dto): User {
            $user = new User;
            $user->tenant_id = auth()->user()->tenant_id;
            $user->name = $dto->name;
            $user->email = $dto->email;
            $user->password = $dto->password;
            $user->role_id = $dto->role_id;
            $user->save();

            if ($dto->storeIds !== [] || AllocatedStores::ids() !== null) {
                return $this->syncStores->sync($user, $dto->storeIds);
            }

            return $user->load(['role', 'stores']);
        });
    }
}
