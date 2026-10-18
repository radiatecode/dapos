<?php

namespace DA\Inventory\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Barryvdh\Snappy\Facades\SnappyPdf;
use DA\Inventory\Actions\Purchase\CreatePurchase;
use DA\Inventory\Actions\Purchase\DeletePurchase;
use DA\Inventory\Actions\Purchase\PreparePurchaseOrderPdf;
use DA\Inventory\Actions\Purchase\RecordPurchasePayment;
use DA\Inventory\Actions\Purchase\UpdatePurchase;
use DA\Inventory\Actions\Purchase\UpdatePurchaseStatus;
use DA\Inventory\Enums\PurchasePaymentStatus;
use DA\Inventory\Enums\PurchaseStatus;
use DA\Inventory\Http\Requests\Api\V1\Purchase\StorePurchasePaymentRequest;
use DA\Inventory\Http\Requests\Api\V1\Purchase\StorePurchaseRequest;
use DA\Inventory\Http\Requests\Api\V1\Purchase\UpdatePurchaseRequest;
use DA\Inventory\Http\Requests\Api\V1\Purchase\UpdatePurchaseStatusRequest;
use DA\Inventory\Http\Resources\Api\V1\PurchaseResource;
use DA\Inventory\Models\Purchase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class PurchaseController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = validator([
            'per_page' => $request->input('per_page', $request->input('limit', 15)),
            'status' => $request->filled('status') ? $request->input('status') : null,
            'payment_status' => $request->filled('payment_status') ? $request->input('payment_status') : null,
            'supplier_id' => $request->filled('supplier_id') ? $request->input('supplier_id') : null,
            'search' => $request->filled('search') ? $request->input('search') : null,
            'date_from' => $request->filled('date_from') ? $request->input('date_from') : null,
            'date_to' => $request->filled('date_to') ? $request->input('date_to') : null,
        ], [
            'per_page' => ['required', 'integer', 'min:1', 'max:100'],
            'status' => ['nullable', Rule::enum(PurchaseStatus::class)],
            'payment_status' => ['nullable', Rule::enum(PurchasePaymentStatus::class)],
            'supplier_id' => ['nullable', 'integer', 'min:1'],
            'search' => ['nullable', 'string', 'max:100'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', Rule::when($request->filled('date_from'), ['after_or_equal:date_from'])],
        ])->validate();

        return PurchaseResource::collection(
            Purchase::queries()->paginateNewestFirst(
                (int) $filters['per_page'],
                isset($filters['status']) ? PurchaseStatus::from($filters['status']) : null,
                isset($filters['payment_status']) ? PurchasePaymentStatus::from($filters['payment_status']) : null,
                isset($filters['supplier_id']) ? (int) $filters['supplier_id'] : null,
                $filters['search'] ?? null,
                $filters['date_from'] ?? null,
                $filters['date_to'] ?? null,
            ),
        );
    }

    public function summary(): JsonResponse
    {
        return response()->json([
            'data' => Purchase::queries()->summary(),
        ]);
    }

    public function store(StorePurchaseRequest $request, CreatePurchase $create): JsonResponse
    {
        $purchase = $create->handle($request->toDTO());

        return PurchaseResource::make($purchase)
            ->response()
            ->setStatusCode(201);
    }

    public function show(int $id): PurchaseResource
    {
        return PurchaseResource::make(Purchase::queries()->findForDetail($id));
    }

    public function storePayment(StorePurchasePaymentRequest $request, int $id, RecordPurchasePayment $record): JsonResponse
    {
        $purchase = $record->handle($id, $request->toDTO());

        return PurchaseResource::make($purchase)
            ->response()
            ->setStatusCode(201);
    }

    public function pdf(int $id, PreparePurchaseOrderPdf $prepare): Response
    {
        $document = $prepare->handle($id);

        return SnappyPdf::loadView('inventory::purchases.order', ['document' => $document])
            ->setPaper('a4', 'portrait')
            ->setOption('margin-top', 12)
            ->setOption('margin-right', 10)
            ->setOption('margin-bottom', 12)
            ->setOption('margin-left', 10)
            ->setOption('footer-right', 'Page [page] of [toPage]')
            ->setOption('footer-font-size', 8)
            ->download($document->fileName());
    }

    public function update(UpdatePurchaseRequest $request, int $id, UpdatePurchase $update): PurchaseResource
    {
        return PurchaseResource::make($update->handle($id, $request->toDTO()));
    }

    public function updateStatus(UpdatePurchaseStatusRequest $request, int $id, UpdatePurchaseStatus $updateStatus): PurchaseResource
    {
        return PurchaseResource::make($updateStatus->handle($id, $request->status()));
    }

    public function destroy(Request $request, DeletePurchase $delete): JsonResponse
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
            'message' => 'Purchase deleted successfully',
        ], Response::HTTP_OK);
    }
}
