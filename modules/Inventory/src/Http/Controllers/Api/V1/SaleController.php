<?php

namespace DA\Inventory\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use DA\Inventory\Actions\Sale\CreateSale;
use DA\Inventory\Actions\Sale\RecordSalePayment;
use DA\Inventory\Enums\SalePaymentStatus;
use DA\Inventory\Enums\SaleStatus;
use DA\Inventory\Http\Requests\Api\V1\Sale\StoreSalePaymentRequest;
use DA\Inventory\Http\Requests\Api\V1\Sale\StoreSaleRequest;
use DA\Inventory\Http\Resources\Api\V1\SaleResource;
use DA\Inventory\Models\Sale;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class SaleController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $this->validatedFilters($request);

        return SaleResource::collection(
            Sale::queries()->paginateNewestFirst((int) $filters['per_page'], ...$this->queryFilters($filters)),
        );
    }

    public function summary(Request $request): JsonResponse
    {
        return response()->json([
            'data' => Sale::queries()->summary(...$this->queryFilters($this->validatedFilters($request))),
        ]);
    }

    public function store(StoreSaleRequest $request, CreateSale $create): JsonResponse
    {
        $sale = $create->handle($request->toDTO());

        return SaleResource::make($sale)
            ->response()
            ->setStatusCode(201);
    }

    public function show(int $id): SaleResource
    {
        return SaleResource::make(Sale::queries()->findForDetail($id));
    }

    public function storePayment(StoreSalePaymentRequest $request, int $id, RecordSalePayment $record): JsonResponse
    {
        $sale = $record->handle($id, $request->toDTO());

        return SaleResource::make($sale)
            ->response()
            ->setStatusCode(201);
    }

    /**
     * @return array{
     *     per_page: int|string,
     *     status: string|null,
     *     payment_status: string|null,
     *     customer_id: int|string|null,
     *     store_id: int|string|null,
     *     search: string|null,
     *     date_from: string|null,
     *     date_to: string|null
     * }
     */
    private function validatedFilters(Request $request): array
    {
        return validator([
            'per_page' => $request->input('per_page', $request->input('limit', 15)),
            'status' => $request->filled('status') ? $request->input('status') : null,
            'payment_status' => $request->filled('payment_status') ? $request->input('payment_status') : null,
            'customer_id' => $request->filled('customer_id') ? $request->input('customer_id') : null,
            'store_id' => $request->filled('store_id') ? $request->input('store_id') : null,
            'search' => $request->filled('search') ? $request->input('search') : null,
            'date_from' => $request->filled('date_from') ? $request->input('date_from') : null,
            'date_to' => $request->filled('date_to') ? $request->input('date_to') : null,
        ], [
            'per_page' => ['required', 'integer', 'min:1', 'max:100'],
            'status' => ['nullable', Rule::enum(SaleStatus::class)],
            'payment_status' => ['nullable', Rule::enum(SalePaymentStatus::class)],
            'customer_id' => ['nullable', 'integer', 'min:1'],
            'store_id' => ['nullable', 'integer', 'min:1'],
            'search' => ['nullable', 'string', 'max:100'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', Rule::when($request->filled('date_from'), ['after_or_equal:date_from'])],
        ])->validate();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{
     *     status: SaleStatus|null,
     *     paymentStatus: SalePaymentStatus|null,
     *     customerId: int|null,
     *     storeId: int|null,
     *     search: string|null,
     *     dateFrom: string|null,
     *     dateTo: string|null
     * }
     */
    private function queryFilters(array $filters): array
    {
        return [
            'status' => isset($filters['status']) ? SaleStatus::from($filters['status']) : null,
            'paymentStatus' => isset($filters['payment_status']) ? SalePaymentStatus::from($filters['payment_status']) : null,
            'customerId' => isset($filters['customer_id']) ? (int) $filters['customer_id'] : null,
            'storeId' => isset($filters['store_id']) ? (int) $filters['store_id'] : null,
            'search' => $filters['search'] ?? null,
            'dateFrom' => $filters['date_from'] ?? null,
            'dateTo' => $filters['date_to'] ?? null,
        ];
    }
}
