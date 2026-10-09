<?php

namespace DA\Inventory\Http\Controllers\Api\V1;

use App\Enums\Permission;
use App\Http\Controllers\Controller;
use DA\Inventory\Actions\Product\CreateProduct;
use DA\Inventory\Actions\Product\UpdateProduct;
use DA\Inventory\Http\Requests\Api\V1\Product\StoreProductRequest;
use DA\Inventory\Http\Requests\Api\V1\Product\UpdateProductRequest;
use DA\Inventory\Http\Resources\Api\V1\ProductResource;
use DA\Inventory\Models\Product;
use DA\Inventory\Services\VariantCodeGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = validator([
            'per_page' => $request->input('per_page', $request->input('limit', 15)),
            'name' => $request->filled('name') ? $request->input('name') : null,
            'search' => $request->filled('search') ? $request->input('search') : null,
            'category_id' => $request->filled('category_id') ? $request->input('category_id') : null,
            'brand_id' => $request->filled('brand_id') ? $request->input('brand_id') : null,
            'is_active' => $request->filled('is_active') ? $request->input('is_active') : null,
        ], [
            'per_page' => ['required', 'integer', 'min:1', 'max:100'],
            'name' => ['nullable', 'string', 'max:100'],
            'search' => ['nullable', 'string', 'max:100'],
            'category_id' => ['nullable', 'integer', 'min:1'],
            'brand_id' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['nullable', 'boolean'],
        ])->validate();

        return ProductResource::collection(
            Product::queries()->paginateNewestFirst(
                (int) $filters['per_page'],
                $filters['name'] ?? null,
                $filters['search'] ?? null,
                isset($filters['category_id']) ? (int) $filters['category_id'] : null,
                isset($filters['brand_id']) ? (int) $filters['brand_id'] : null,
                isset($filters['is_active']) ? filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN) : null,
            ),
        );
    }

    public function summary(): JsonResponse
    {
        return response()->json([
            'data' => Product::queries()->summary(),
        ]);
    }

    public function generateSku(VariantCodeGenerator $generator): JsonResponse
    {
        $this->authorizeProductCodeGeneration();

        return response()->json([
            'sku' => $generator->nextSku(),
        ]);
    }

    public function generateBarcode(Request $request, VariantCodeGenerator $generator): JsonResponse
    {
        $this->authorizeProductCodeGeneration();

        $reserved = $request->input('reserved', []);

        if (is_string($reserved) && $reserved !== '') {
            $reserved = [$reserved];
        }

        $reserved = is_array($reserved)
            ? array_values(array_filter($reserved, fn (mixed $value): bool => is_string($value) && $value !== ''))
            : [];

        return response()->json([
            'barcode' => $generator->nextBarcode($reserved),
        ]);
    }

    public function store(StoreProductRequest $request, CreateProduct $create): JsonResponse
    {
        $product = $create->handle($request->toDTO());

        return ProductResource::make($product)
            ->response()
            ->setStatusCode(201);
    }

    public function show(int $id): ProductResource
    {
        return ProductResource::make(Product::queries()->findForDetail($id));
    }

    public function update(UpdateProductRequest $request, int $id, UpdateProduct $update): ProductResource
    {
        return ProductResource::make($update->handle($id, $request->toDTO()));
    }

    private function authorizeProductCodeGeneration(): void
    {
        $user = auth()->user();

        if (
            ! $user->is_super_admin
            && ! $user->hasPermission(Permission::ProductsCreate)
            && ! $user->hasPermission(Permission::ProductsUpdate)
        ) {
            abort(403, 'You do not have permission to perform this action.');
        }
    }
}
