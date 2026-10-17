<?php

namespace DA\Inventory\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use DA\Inventory\Actions\QuickSale\CreateQuickSale;
use DA\Inventory\Http\Requests\Api\V1\QuickSale\QuickSaleFilterRequest;
use DA\Inventory\Http\Requests\Api\V1\QuickSale\StoreQuickSaleRequest;
use DA\Inventory\Http\Resources\Api\V1\QuickSaleResource;
use DA\Inventory\Models\QuickSale;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class QuickSaleController extends Controller
{
    public function index(QuickSaleFilterRequest $request): AnonymousResourceCollection
    {
        return QuickSaleResource::collection(
            QuickSale::queries()->paginateNewestFirst($request->toDTO(), $request->perPage()),
        );
    }

    public function summary(QuickSaleFilterRequest $request): JsonResponse
    {
        return response()->json([
            'data' => QuickSale::queries()->summary($request->toDTO()),
        ]);
    }

    public function store(StoreQuickSaleRequest $request, CreateQuickSale $create): JsonResponse
    {
        return QuickSaleResource::make($create->handle($request->toDTO()))
            ->response()
            ->setStatusCode(201);
    }

    public function show(int $id): QuickSaleResource
    {
        return QuickSaleResource::make(QuickSale::queries()->findForDetail($id));
    }
}
