<?php

namespace DA\Inventory\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use DA\Inventory\Actions\Supplier\CreateSupplier;
use DA\Inventory\Actions\Supplier\DeleteSupplier;
use DA\Inventory\Actions\Supplier\UpdateSupplier;
use DA\Inventory\Http\Requests\Api\V1\Supplier\StoreSupplierRequest;
use DA\Inventory\Http\Requests\Api\V1\Supplier\UpdateSupplierRequest;
use DA\Inventory\Http\Resources\Api\V1\SupplierResource;
use DA\Inventory\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class SupplierController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = $request->integer('per_page', $request->integer('limit', 15));
        $name = $request->string('name')->toString();
        $email = $request->string('email')->toString();
        $phone = $request->string('phone')->toString();

        return SupplierResource::collection(
            Supplier::queries()->paginateNewestFirst(
                $perPage,
                $name !== '' ? $name : null,
                $email !== '' ? $email : null,
                $phone !== '' ? $phone : null,
            ),
        );
    }

    public function store(StoreSupplierRequest $request, CreateSupplier $create): JsonResponse
    {
        $supplier = $create->handle($request->toDTO());

        return SupplierResource::make($supplier)
            ->response()
            ->setStatusCode(201);
    }

    public function show(int $id): SupplierResource
    {
        return SupplierResource::make(Supplier::queries()->findForTenant($id));
    }

    public function update(UpdateSupplierRequest $request, int $id, UpdateSupplier $update): SupplierResource
    {
        return SupplierResource::make($update->handle($id, $request->toDTO()));
    }

    public function destroy(Request $request, DeleteSupplier $delete): JsonResponse
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
            'message' => 'Supplier deleted successfully',
        ], Response::HTTP_OK);
    }
}
