<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\User\AssignUserRoles;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\User\AssignRolesRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;

class AssignedUserRoleController extends Controller
{
    public function update(AssignRolesRequest $request, User $user, AssignUserRoles $assign): UserResource
    {
        return UserResource::make($assign->handle($user, $request->toDTO()));
    }
}
