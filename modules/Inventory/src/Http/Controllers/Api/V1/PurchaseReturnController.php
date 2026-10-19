<?php

namespace DA\Inventory\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Barryvdh\Snappy\Facades\SnappyPdf;
use DA\Inventory\Actions\PurchaseReturn\CreatePurchaseReturn;
use DA\Inventory\Actions\PurchaseReturn\DeletePurchaseReturn;
use DA\Inventory\Actions\PurchaseReturn\ListPurchaseReturnLines;
use DA\Inventory\Actions\PurchaseReturn\PreparePurchaseReturnPdf;
use DA\Inventory\Actions\PurchaseReturn\UpdatePurchaseReturn;
use DA\Inventory\Actions\PurchaseReturn\UpdatePurchaseReturnStatus;
use DA\Inventory\Enums\PurchaseReturnRefundStatus;
use DA\Inventory\Enums\PurchaseReturnStatus;
use DA\Inventory\Http\Requests\Api\V1\PurchaseReturn\StorePurchaseReturnRequest;
use DA\Inventory\Http\Requests\Api\V1\PurchaseReturn\UpdatePurchaseReturnRequest;
use DA\Inventory\Http\Requests\Api\V1\PurchaseReturn\UpdatePurchaseReturnStatusRequest;
use DA\Inventory\Http\Resources\Api\V1\PurchaseReturnResource;
use DA\Inventory\Models\PurchaseReturn;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class PurchaseReturnController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = validator([
            'per_page' => $request->input('per_page', $request->input('limit', 15)),
            'status' => $request->filled('status') ? $request->input('status') : null,
            'refund_status' => $request->filled('refund_status') ? $request->input('refund_status') : null,
            'supplier_id' => $request->filled('supplier_id') ? $request->input('supplier_id') : null,
            'store_id' => $request->filled('store_id') ? $request->input('store_id') : null,
            'purchase_id' => $request->filled('purchase_id') ? $request->input('purchase_id') : null,
            'search' => $request->filled('search') ? $request->input('search') : null,
            'date_from' => $request->filled('date_from') ? $request->input('date_from') : null,
            'date_to' => $request->filled('date_to') ? $request->input('date_to') : null,
        ], [
            'per_page' => ['required', 'integer', 'min:1', 'max:100'],
            'status' => ['nullable', Rule::enum(PurchaseReturnStatus::class)],
            'refund_status' => ['nullable', Rule::enum(PurchaseReturnRefundStatus::class)],
            'supplier_id' => ['nullable', 'integer', 'min:1'],
            'store_id' => ['nullable', 'integer', 'min:1'],
            'purchase_id' => ['nullable', 'integer', 'min:1'],
            'search' => ['nullable', 'string', 'max:100'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', Rule::when($request->filled('date_from'), ['after_or_equal:date_from'])],
        ])->validate();

        return PurchaseReturnResource::collection(
            PurchaseReturn::queries()->paginateNewestFirst(
                (int) $filters['per_page'],
                isset($filters['status']) ? PurchaseReturnStatus::from($filters['status']) : null,
                isset($filters['refund_status']) ? PurchaseReturnRefundStatus::from($filters['refund_status']) : null,
                isset($filters['supplier_id']) ? (int) $filters['supplier_id'] : null,
                isset($filters['store_id']) ? (int) $filters['store_id'] : null,
                isset($filters['purchase_id']) ? (int) $filters['purchase_id'] : null,
                $filters['search'] ?? null,
                $filters['date_from'] ?? null,
                $filters['date_to'] ?? null,
            ),
        );
    }

    public function summary(): JsonResponse
    {
        return response()->json([
            'data' => PurchaseReturn::queries()->summary(),
        ]);
    }

    public function purchaseLines(int $id, ListPurchaseReturnLines $lines): JsonResponse
    {
        return response()->json([
            'data' => $lines->handle($id),
        ]);
    }

    public function store(StorePurchaseReturnRequest $request, CreatePurchaseReturn $create): JsonResponse
    {
        $return = $create->handle($request->toDTO());

        return PurchaseReturnResource::make($return)
            ->response()
            ->setStatusCode(201);
    }

    public function show(int $id): PurchaseReturnResource
    {
        return PurchaseReturnResource::make(PurchaseReturn::queries()->findForDetail($id));
    }

    public function pdf(int $id, PreparePurchaseReturnPdf $prepare): Response
    {
        $document = $prepare->handle($id);

        return SnappyPdf::loadView('inventory::purchase-returns.return', ['document' => $document])
            ->setPaper('a4', 'portrait')
            ->setOption('margin-top', 12)
            ->setOption('margin-right', 10)
            ->setOption('margin-bottom', 12)
            ->setOption('margin-left', 10)
            ->setOption('footer-right', 'Page [page] of [toPage]')
            ->setOption('footer-font-size', 8)
            ->download($document->fileName());
    }

    public function update(UpdatePurchaseReturnRequest $request, int $id, UpdatePurchaseReturn $update): PurchaseReturnResource
    {
        return PurchaseReturnResource::make($update->handle($id, $request->toDTO()));
    }

    public function updateStatus(UpdatePurchaseReturnStatusRequest $request, int $id, UpdatePurchaseReturnStatus $updateStatus): PurchaseReturnResource
    {
        return PurchaseReturnResource::make($updateStatus->handle($id, $request->status()));
    }

    public function destroy(Request $request, DeletePurchaseReturn $delete): JsonResponse
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
            'message' => 'Purchase return deleted successfully',
        ], Response::HTTP_OK);
    }
}
