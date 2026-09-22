<?php

namespace App\Actions\Auth;

use App\DTO\Auth\LoginDTO;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LoginUser
{
    public function handle(LoginDTO $dto): LoginResult
    {
        $user = User::queries()->findByEmail($dto->email);

        if ($user === null || ! Hash::check($dto->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        if ($user->tenant === null) {
            throw ValidationException::withMessages([
                'email' => trans('auth.tenant_not_found'),
            ]);
        }

        if ($user->tenant->isSuspended()) {
            abort(403, 'This business account is suspended.');
        }

        $token = $user->createToken($dto->device_name)->plainTextToken;

        return new LoginResult($user->load('roles'), $token);
    }
}
