<?php

namespace DA\Expense\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use DA\Expense\Actions\ExpenseCategory\CreateExpenseCategory;
use DA\Expense\Actions\ExpenseCategory\DeleteExpenseCategory;
use DA\Expense\Actions\ExpenseCategory\UpdateExpenseCategory;
use DA\Expense\Http\Requests\Api\V1\ExpenseCategory\StoreExpenseCategoryRequest;
use DA\Expense\Http\Requests\Api\V1\ExpenseCategory\UpdateExpenseCategoryRequest;
use DA\Expense\Http\Resources\Api\V1\ExpenseCategoryResource;
use DA\Expense\Models\ExpenseCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class ExpenseCategoryController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = $request->integer('per_page', $request->integer('limit', 15));
        $categoryName = $request->string('category_name')->toString();

        return ExpenseCategoryResource::collection(
            ExpenseCategory::queries()->paginateNewestFirst(
                $perPage,
                $categoryName !== '' ? $categoryName : null,
            ),
        );
    }

    public function store(StoreExpenseCategoryRequest $request, CreateExpenseCategory $create): JsonResponse
    {
        $category = $create->handle($request->toDTO());

        return ExpenseCategoryResource::make($category)
            ->response()
            ->setStatusCode(201);
    }

    public function show(int $id): ExpenseCategoryResource
    {
        return ExpenseCategoryResource::make(ExpenseCategory::queries()->findForTenant($id));
    }

    public function update(UpdateExpenseCategoryRequest $request, int $id, UpdateExpenseCategory $update): ExpenseCategoryResource
    {
        return ExpenseCategoryResource::make($update->handle($id, $request->toDTO()));
    }

    public function destroy(Request $request, DeleteExpenseCategory $delete): JsonResponse
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
            'message' => 'Expense category deleted successfully',
        ], Response::HTTP_OK);
    }
}
