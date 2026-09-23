<?php

namespace DA\Inventory\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use DA\Inventory\Actions\Unit\CreateUnit;
use DA\Inventory\Actions\Unit\DeleteUnit;
use DA\Inventory\Actions\Unit\UpdateUnit;
use DA\Inventory\Http\Requests\Api\V1\Unit\StoreUnitRequest;
use DA\Inventory\Http\Requests\Api\V1\Unit\UpdateUnitRequest;
use DA\Inventory\Http\Resources\Api\V1\UnitResource;
use DA\Inventory\Models\Unit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class UnitController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        return UnitResource::collection(
            Unit::queries()->paginateNewestFirst($request->integer('per_page', 15)),
        );
    }

    public function store(StoreUnitRequest $request, CreateUnit $create): JsonResponse
    {
        $unit = $create->handle($request->toDTO());

        return UnitResource::make($unit)
            ->response()
            ->setStatusCode(201);
    }

    public function show(int $id): UnitResource
    {
        return UnitResource::make(Unit::findOrFail($id));
    }

    public function update(UpdateUnitRequest $request, int $id, UpdateUnit $update): UnitResource
    {
        return UnitResource::make($update->handle($id, $request->toDTO()));
    }

    public function destroy(int $id, DeleteUnit $delete)
    {
        $delete->handle($id);

        return response()->json([
            'status' => 'success',
            'message' => 'Unit deleted successfully',
        ], Response::HTTP_OK);
    }
}
