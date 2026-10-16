<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Role\CreateRole;
use App\Actions\Role\UpdateRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Role\StoreRoleRequest;
use App\Http\Requests\Api\V1\Role\UpdateRoleRequest;
use App\Http\Resources\Api\V1\RoleResource;
use App\Models\Role;
use App\Services\RoleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class RoleController extends Controller
{
    public function index(Request $request, RoleService $roles): AnonymousResourceCollection
    {
        return RoleResource::collection(
            $roles->paginate($request->integer('per_page', $request->integer('limit', 15))),
        );
    }

    public function store(StoreRoleRequest $request, CreateRole $create): JsonResponse
    {
        $role = $create->handle($request->toDTO());

        return RoleResource::make($role)
            ->response()
            ->setStatusCode(201);
    }

    public function show(int $id, RoleService $roles): RoleResource
    {
        return RoleResource::make($roles->show($id));
    }

    public function update(UpdateRoleRequest $request, int $id, UpdateRole $update): RoleResource
    {
        return RoleResource::make($update->handle($id, $request->toDTO()));
    }

    public function destroy(int $id): JsonResponse
    {
        $role = Role::queries()->findById($id);

        $role->delete();

        return response()->json([
            'message' => 'Role deleted successfully',
        ], Response::HTTP_OK);
    }
}
