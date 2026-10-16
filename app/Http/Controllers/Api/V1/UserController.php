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
            $users->paginate($request->integer('per_page', $request->integer('limit', 15))),
        );
    }

    public function store(StoreUserRequest $request, CreateUser $create): JsonResponse
    {
        $user = $create->handle($request->toDTO());

        return UserResource::make($user)
            ->response()
            ->setStatusCode(201);
    }

    public function show(int $id, UserService $users): UserResource
    {
        return UserResource::make($users->show($id));
    }

    public function update(UpdateUserRequest $request, int $id, UpdateUser $update): UserResource
    {
        return UserResource::make($update->handle($id, $request->toDTO()));
    }

    public function destroy(int $id, DeleteUser $delete): Response
    {
        $user = User::queries()->findById($id);

        if ($user->is_super_admin) {
            throw new \Exception('Super admin cannot be deleted');
        }

        $delete->handle($user);

        return response()->noContent();
    }
}
