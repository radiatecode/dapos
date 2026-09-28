<?php

namespace DA\Inventory\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use DA\Inventory\Actions\StockTransfer\CreateStockTransfer;
use DA\Inventory\Actions\StockTransfer\UpdateStockTransfer;
use DA\Inventory\Actions\StockTransfer\UpdateStockTransferStatus;
use DA\Inventory\Enums\StockTransferStatus;
use DA\Inventory\Http\Requests\Api\V1\StockTransfer\StoreStockTransferRequest;
use DA\Inventory\Http\Requests\Api\V1\StockTransfer\UpdateStockTransferRequest;
use DA\Inventory\Http\Requests\Api\V1\StockTransfer\UpdateStockTransferStatusRequest;
use DA\Inventory\Http\Resources\Api\V1\StockTransferResource;
use DA\Inventory\Models\StockTransfer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class StockTransferController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'status' => ['sometimes', Rule::enum(StockTransferStatus::class)],
        ]);

        return StockTransferResource::collection(
            StockTransfer::queries()->paginateNewestFirst(
                (int) ($filters['per_page'] ?? 15),
                isset($filters['status']) ? StockTransferStatus::from($filters['status']) : null,
            ),
        );
    }

    public function store(StoreStockTransferRequest $request, CreateStockTransfer $create): JsonResponse
    {
        $transfer = $create->handle($request->toDTO());

        return StockTransferResource::make($transfer)
            ->response()
            ->setStatusCode(201);
    }

    public function show(int $id): StockTransferResource
    {
        return StockTransferResource::make(StockTransfer::queries()->findForDetail($id));
    }

    public function update(UpdateStockTransferRequest $request, int $id, UpdateStockTransfer $update): StockTransferResource
    {
        return StockTransferResource::make($update->handle($id, $request->toDTO()));
    }

    public function updateStatus(UpdateStockTransferStatusRequest $request, int $id, UpdateStockTransferStatus $updateStatus): StockTransferResource
    {
        return StockTransferResource::make($updateStatus->handle($id, $request->status()));
    }
}
