<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\User\CreateUser;
use App\Actions\User\DeleteUser;
use App\Actions\User\UpdateUser;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\User\StoreUserRequest;
use App\Http\Requests\Api\V1\User\UpdateUserRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class UserController extends Controller
{
    public function index(Request $request, UserService $users): AnonymousResourceCollection
    {
        return UserResource::collection(
            $users->paginate($request->integer('per_page', 15)),
        );
    }

    public function store(StoreUserRequest $request, CreateUser $create): JsonResponse
    {
        $user = $create->handle($request->toDTO());

        return UserResource::make($user)
            ->response()
            ->setStatusCode(201);
    }

    public function show(User $user, UserService $users): UserResource
    {
        return UserResource::make($users->show($user));
    }

    public function update(UpdateUserRequest $request, User $user, UpdateUser $update): UserResource
    {
        return UserResource::make($update->handle($user, $request->toDTO()));
    }

    public function destroy(User $user, DeleteUser $delete): Response
    {
        $this->authorize('delete', $user);

        $delete->handle($user);

        return response()->noContent();
    }
}
