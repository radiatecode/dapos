<?php

namespace DA\Inventory\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use DA\Inventory\Actions\Brand\CreateBrand;
use DA\Inventory\Actions\Brand\DeleteBrand;
use DA\Inventory\Actions\Brand\UpdateBrand;
use DA\Inventory\Http\Requests\Api\V1\Brand\StoreBrandRequest;
use DA\Inventory\Http\Requests\Api\V1\Brand\UpdateBrandRequest;
use DA\Inventory\Http\Resources\Api\V1\BrandResource;
use DA\Inventory\Models\Brand;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class BrandController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = $request->integer('per_page', $request->integer('limit', 15));
        $name = $request->string('name')->toString();

        return BrandResource::collection(
            Brand::queries()->paginateNewestFirst(
                $perPage,
                $name !== '' ? $name : null,
            ),
        );
    }

    public function store(StoreBrandRequest $request, CreateBrand $create): JsonResponse
    {
        $brand = $create->handle($request->toDTO());

        return BrandResource::make($brand)
            ->response()
            ->setStatusCode(201);
    }

    public function show(int $brandId): BrandResource
    {
        return BrandResource::make(Brand::findOrFail($brandId));
    }

    public function update(UpdateBrandRequest $request, int $brandId, UpdateBrand $update): BrandResource
    {
        return BrandResource::make($update->handle($brandId, $request->toDTO()));
    }

    public function destroy(Request $request, DeleteBrand $delete)
    {
        if ($request->input('action') !== 'delete') {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid action',
            ], Response::HTTP_BAD_REQUEST);
        }

        $delete->handle($request->input('ids'));

        return response()->json([
            'status' => 'success',
            'message' => 'Brand deleted successfully',
        ], Response::HTTP_OK);
    }
}
