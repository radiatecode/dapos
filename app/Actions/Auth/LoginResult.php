<?php

namespace App\Actions\Auth;

use App\Models\User;

class LoginResult
{
    public function __construct(
        public User $user,
        public string $token,
        public string $tokenType = 'Bearer',
    ) {}
}
