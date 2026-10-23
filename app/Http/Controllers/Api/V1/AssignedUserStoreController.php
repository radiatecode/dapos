<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\User\SyncUserStores;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\User\AssignStoresRequest;
use App\Http\Resources\Api\V1\UserResource;

class AssignedUserStoreController extends Controller
{
    public function update(AssignStoresRequest $request, int $id, SyncUserStores $sync): UserResource
    {
        return UserResource::make($sync->handle($id, $request->toDTO()));
    }
}
