<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\User\UpdateUserPassword;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\User\UpdatePasswordRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;

class UserPasswordController extends Controller
{
    public function update(UpdatePasswordRequest $request, User $user, UpdateUserPassword $update): UserResource
    {
        return UserResource::make($update->handle($user, $request->toDTO()));
    }
}
