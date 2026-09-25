<?php

namespace DA\Inventory\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use DA\Inventory\Actions\Attribute\CreateAttribute;
use DA\Inventory\Actions\Attribute\DeleteAttribute;
use DA\Inventory\Actions\Attribute\UpdateAttribute;
use DA\Inventory\Http\Requests\Api\V1\Attribute\StoreAttributeRequest;
use DA\Inventory\Http\Requests\Api\V1\Attribute\UpdateAttributeRequest;
use DA\Inventory\Http\Resources\Api\V1\AttributeResource;
use DA\Inventory\Models\Attribute;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class AttributeController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = $request->integer('per_page', $request->integer('limit', 15));
        $name = $request->string('name')->toString();

        if ($name === '') {
            $name = $request->string('search')->toString();
        }

        return AttributeResource::collection(
            Attribute::queries()->paginateBySortOrder(
                $perPage,
                $name !== '' ? $name : null,
            ),
        );
    }

    public function store(StoreAttributeRequest $request, CreateAttribute $create): JsonResponse
    {
        $attribute = $create->handle($request->toDTO());

        return AttributeResource::make($attribute)
            ->response()
            ->setStatusCode(201);
    }

    public function show(int $id): AttributeResource
    {
        return AttributeResource::make(Attribute::with('values')->findOrFail($id));
    }

    public function update(UpdateAttributeRequest $request, int $id, UpdateAttribute $update): AttributeResource
    {
        return AttributeResource::make($update->handle($id, $request->toDTO()));
    }

    public function destroy(Request $request, DeleteAttribute $delete)
    {
        if ($request->input('action') !== 'delete') {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid action',
            ], Response::HTTP_BAD_REQUEST);
        }

        if (! $request->has('ids')) {
            return response()->json([
                'status' => 'error',
                'message' => 'IDs are required',
            ], Response::HTTP_BAD_REQUEST);
        }

        $delete->handle($request->input('ids'));

        return response()->json([
            'status' => 'success',
            'message' => 'Attribute deleted successfully',
        ], Response::HTTP_OK);
    }
}
