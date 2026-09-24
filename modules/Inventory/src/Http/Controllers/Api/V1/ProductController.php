<?php

namespace DA\Inventory\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use DA\Inventory\Actions\Product\CreateProduct;
use DA\Inventory\Actions\Product\UpdateProduct;
use DA\Inventory\Http\Requests\Api\V1\Product\StoreProductRequest;
use DA\Inventory\Http\Requests\Api\V1\Product\UpdateProductRequest;
use DA\Inventory\Http\Resources\Api\V1\ProductResource;
use DA\Inventory\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        return ProductResource::collection(
            Product::queries()->paginateNewestFirst($request->integer('per_page', 15)),
        );
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
}
