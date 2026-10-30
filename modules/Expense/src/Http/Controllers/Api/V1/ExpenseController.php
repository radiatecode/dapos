<?php

namespace DA\Expense\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use DA\Expense\Actions\Expense\CreateExpense;
use DA\Expense\Actions\Expense\DeleteExpense;
use DA\Expense\Actions\Expense\UpdateExpense;
use DA\Expense\Actions\Expense\UpdateExpenseStatus;
use DA\Expense\Enums\ExpenseStatus;
use DA\Expense\Http\Requests\Api\V1\Expense\StoreExpenseRequest;
use DA\Expense\Http\Requests\Api\V1\Expense\UpdateExpenseRequest;
use DA\Expense\Http\Requests\Api\V1\Expense\UpdateExpenseStatusRequest;
use DA\Expense\Http\Resources\Api\V1\ExpenseResource;
use DA\Expense\Models\Expense;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class ExpenseController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = validator([
            'per_page' => $request->input('per_page', $request->input('limit', 15)),
            'status' => $request->filled('status') ? $request->input('status') : null,
            'category_id' => $request->filled('category_id') ? $request->input('category_id') : null,
            'store_id' => $request->filled('store_id') ? $request->input('store_id') : null,
            'search' => $request->filled('search') ? $request->input('search') : null,
            'date_from' => $request->filled('date_from') ? $request->input('date_from') : null,
            'date_to' => $request->filled('date_to') ? $request->input('date_to') : null,
        ], [
            'per_page' => ['required', 'integer', 'min:1', 'max:100'],
            'status' => ['nullable', Rule::enum(ExpenseStatus::class)],
            'category_id' => ['nullable', 'integer', 'min:1'],
            'store_id' => ['nullable', 'integer', 'min:1'],
            'search' => ['nullable', 'string', 'max:100'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', Rule::when($request->filled('date_from'), ['after_or_equal:date_from'])],
        ])->validate();

        return ExpenseResource::collection(
            Expense::queries()->paginateNewestFirst(
                (int) $filters['per_page'],
                isset($filters['status']) ? ExpenseStatus::from($filters['status']) : null,
                isset($filters['category_id']) ? (int) $filters['category_id'] : null,
                isset($filters['store_id']) ? (int) $filters['store_id'] : null,
                $filters['search'] ?? null,
                $filters['date_from'] ?? null,
                $filters['date_to'] ?? null,
            ),
        );
    }

    public function summary(): JsonResponse
    {
        return response()->json([
            'data' => Expense::queries()->summary(),
        ]);
    }

    public function store(StoreExpenseRequest $request, CreateExpense $create): JsonResponse
    {
        $expense = $create->handle($request->toDTO());

        return ExpenseResource::make($expense)
            ->response()
            ->setStatusCode(201);
    }

    public function show(int $id): ExpenseResource
    {
        return ExpenseResource::make(Expense::queries()->findForTenant($id));
    }

    public function update(UpdateExpenseRequest $request, int $id, UpdateExpense $update): ExpenseResource
    {
        return ExpenseResource::make($update->handle($id, $request->toDTO()));
    }

    public function updateStatus(UpdateExpenseStatusRequest $request, int $id, UpdateExpenseStatus $updateStatus): ExpenseResource
    {
        return ExpenseResource::make($updateStatus->handle($id, $request->status()));
    }

    public function destroy(Request $request, DeleteExpense $delete): JsonResponse
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
            'message' => 'Expense deleted successfully',
        ], Response::HTTP_OK);
    }
}
