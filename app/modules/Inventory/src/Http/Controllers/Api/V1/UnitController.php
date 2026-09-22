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
use DA\Inventory\Services\UnitService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class UnitController extends Controller
{
    public function index(Request $request, UnitService $units): AnonymousResourceCollection
    {
        return UnitResource::collection(
            $units->paginate($request->integer('per_page', 15)),
        );
    }

    public function store(StoreUnitRequest $request, CreateUnit $create): JsonResponse
    {
        $unit = $create->handle($request->toDTO());

        return UnitResource::make($unit)
            ->response()
            ->setStatusCode(201);
    }

    public function show(Unit $unit, UnitService $units): UnitResource
    {
        return UnitResource::make($units->show($unit));
    }

    public function update(UpdateUnitRequest $request, Unit $unit, UpdateUnit $update): UnitResource
    {
        return UnitResource::make($update->handle($unit, $request->toDTO()));
    }

    public function destroy(Unit $unit, DeleteUnit $delete): Response
    {
        $this->authorize('delete', $unit);

        $delete->handle($unit);

        return response()->noContent();
    }
}
