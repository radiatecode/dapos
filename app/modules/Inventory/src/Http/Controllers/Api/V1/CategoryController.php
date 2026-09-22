<?php

namespace DA\Inventory\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use DA\Inventory\Actions\Category\CreateCategory;
use DA\Inventory\Actions\Category\DeleteCategory;
use DA\Inventory\Actions\Category\UpdateCategory;
use DA\Inventory\Http\Requests\Api\V1\Category\StoreCategoryRequest;
use DA\Inventory\Http\Requests\Api\V1\Category\UpdateCategoryRequest;
use DA\Inventory\Http\Resources\Api\V1\CategoryResource;
use DA\Inventory\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * Use policy to authorize the request for test
 *
 * But we will not use it any other place in the codebase.
 */
class CategoryController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Category::class);

        return CategoryResource::collection(Category::queries()->tree());
    }

    public function store(StoreCategoryRequest $request, CreateCategory $create): JsonResponse
    {
        $category = $create->handle($request->toDTO());

        return CategoryResource::make($category)
            ->response()
            ->setStatusCode(201);
    }

    public function show(Category $category): CategoryResource
    {
        return CategoryResource::make($category->load(['parent', 'children']));
    }

    public function update(UpdateCategoryRequest $request, Category $category, UpdateCategory $update): CategoryResource
    {
        return CategoryResource::make($update->handle($category, $request->toDTO()));
    }

    public function destroy(Category $category, DeleteCategory $delete)
    {
        $this->authorize('delete', $category);

        $delete->handle($category);

        return response()->json([
            'status' => 'success',
            'message' => 'Category deleted successfully',
        ], Response::HTTP_OK);
    }
}
