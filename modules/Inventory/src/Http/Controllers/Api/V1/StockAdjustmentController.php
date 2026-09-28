<?php

namespace DA\Inventory\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use DA\Inventory\Actions\StockAdjustment\CreateStockAdjustment;
use DA\Inventory\Actions\StockAdjustment\UpdateStockAdjustment;
use DA\Inventory\Actions\StockAdjustment\UpdateStockAdjustmentStatus;
use DA\Inventory\Enums\StockAdjustmentStatus;
use DA\Inventory\Http\Requests\Api\V1\StockAdjustment\StoreStockAdjustmentRequest;
use DA\Inventory\Http\Requests\Api\V1\StockAdjustment\UpdateStockAdjustmentRequest;
use DA\Inventory\Http\Requests\Api\V1\StockAdjustment\UpdateStockAdjustmentStatusRequest;
use DA\Inventory\Http\Resources\Api\V1\StockAdjustmentResource;
use DA\Inventory\Models\StockAdjustment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class StockAdjustmentController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = validator([
            'per_page' => $request->input('per_page', $request->input('limit', 15)),
            'status' => $request->filled('status') ? $request->input('status') : null,
            'store_id' => $request->filled('store_id') ? $request->input('store_id') : null,
        ], [
            'per_page' => ['required', 'integer', 'min:1', 'max:100'],
            'status' => ['nullable', Rule::enum(StockAdjustmentStatus::class)],
            'store_id' => ['nullable', 'integer'],
        ])->validate();

        return StockAdjustmentResource::collection(
            StockAdjustment::queries()->paginateNewestFirst(
                (int) $filters['per_page'],
                isset($filters['status']) ? StockAdjustmentStatus::from($filters['status']) : null,
                isset($filters['store_id']) ? (int) $filters['store_id'] : null,
            ),
        );
    }

    public function store(StoreStockAdjustmentRequest $request, CreateStockAdjustment $create): JsonResponse
    {
        $adjustment = $create->handle($request->toDTO());

        return StockAdjustmentResource::make($adjustment)
            ->response()
            ->setStatusCode(201);
    }

    public function show(int $id): StockAdjustmentResource
    {
        return StockAdjustmentResource::make(StockAdjustment::queries()->findForDetail($id));
    }

    public function update(UpdateStockAdjustmentRequest $request, int $id, UpdateStockAdjustment $update): StockAdjustmentResource
    {
        return StockAdjustmentResource::make($update->handle($id, $request->toDTO()));
    }

    public function updateStatus(UpdateStockAdjustmentStatusRequest $request, int $id, UpdateStockAdjustmentStatus $updateStatus): StockAdjustmentResource
    {
        return StockAdjustmentResource::make($updateStatus->handle($id, $request->status()));
    }
}
