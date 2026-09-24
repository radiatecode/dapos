<?php

namespace DA\Inventory\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use DA\Inventory\Actions\AttributeValue\CreateAttributeValue;
use DA\Inventory\Actions\AttributeValue\DeleteAttributeValue;
use DA\Inventory\Actions\AttributeValue\UpdateAttributeValue;
use DA\Inventory\Http\Requests\Api\V1\AttributeValue\StoreAttributeValueRequest;
use DA\Inventory\Http\Requests\Api\V1\AttributeValue\UpdateAttributeValueRequest;
use DA\Inventory\Http\Resources\Api\V1\AttributeValueResource;
use DA\Inventory\Models\AttributeValue;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class AttributeValueController extends Controller
{
    public function index(Request $request, int $id): AnonymousResourceCollection
    {
        return AttributeValueResource::collection(
            AttributeValue::queries()->paginateForAttribute($id, $request->integer('per_page', 15)),
        );
    }

    public function store(StoreAttributeValueRequest $request, int $id, CreateAttributeValue $create): JsonResponse
    {
        $value = $create->handle($id, $request->toDTO());

        return AttributeValueResource::make($value)
            ->response()
            ->setStatusCode(201);
    }

    public function show(int $id, int $valueId): AttributeValueResource
    {
        return AttributeValueResource::make(
            AttributeValue::query()->where('attribute_id', $id)->findOrFail($valueId),
        );
    }

    public function update(
        UpdateAttributeValueRequest $request,
        int $id,
        int $valueId,
        UpdateAttributeValue $update,
    ): AttributeValueResource {
        return AttributeValueResource::make($update->handle($id, $valueId, $request->toDTO()));
    }

    public function destroy(int $id, int $valueId, DeleteAttributeValue $delete)
    {
        $delete->handle($id, $valueId);

        return response()->json([
            'status' => 'success',
            'message' => 'Attribute value deleted successfully',
        ], Response::HTTP_OK);
    }
}
