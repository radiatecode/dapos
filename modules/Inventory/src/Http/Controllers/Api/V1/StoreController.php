<?php

namespace DA\Inventory\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use DA\Inventory\Actions\Store\CreateStore;
use DA\Inventory\Actions\Store\DeleteStore;
use DA\Inventory\Actions\Store\UpdateStore;
use DA\Inventory\Http\Requests\Api\V1\Store\StoreStoreRequest;
use DA\Inventory\Http\Requests\Api\V1\Store\UpdateStoreRequest;
use DA\Inventory\Http\Resources\Api\V1\StoreResource;
use DA\Inventory\Models\Store;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class StoreController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = $request->integer('per_page', $request->integer('limit', 15));
        $name = $request->string('name')->toString();

        return StoreResource::collection(
            Store::queries()->paginateNewestFirst(
                $perPage,
                $name !== '' ? $name : null,
            ),
        );
    }

    public function store(StoreStoreRequest $request, CreateStore $create): JsonResponse
    {
        $store = $create->handle($request->toDTO());

        return StoreResource::make($store)
            ->response()
            ->setStatusCode(201);
    }

    public function show(int $id): StoreResource
    {
        return StoreResource::make(Store::queries()->findForTenant($id));
    }

    public function update(UpdateStoreRequest $request, int $id, UpdateStore $update): StoreResource
    {
        return StoreResource::make($update->handle($id, $request->toDTO()));
    }

    public function destroy(Request $request, DeleteStore $delete): JsonResponse
    {
        if ($request->input('action') !== 'delete') {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid action',
            ], Response::HTTP_BAD_REQUEST);
        }

        $delete->handle($request->input('ids', []));

        return response()->json([
            'status' => 'success',
            'message' => 'Store deleted successfully',
        ], Response::HTTP_OK);
    }
}
