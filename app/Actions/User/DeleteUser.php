<?php

namespace App\Actions\User;

use App\Models\User;

class DeleteUser
{
    public function handle(User $user): void
    {
        $user->tokens()->delete();
        $user->delete();
    }
}
