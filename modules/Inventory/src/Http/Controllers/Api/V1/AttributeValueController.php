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
    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = $request->integer('per_page', $request->integer('limit', 15));
        $attributeId = $request->integer('attribute_id');
        $value = $request->string('value')->toString();

        return AttributeValueResource::collection(
            AttributeValue::queries()->paginateForAttribute(
                $perPage,
                $attributeId > 0 ? $attributeId : null,
                $value !== '' ? $value : null,
            ),
        );
    }

    public function store(StoreAttributeValueRequest $request, CreateAttributeValue $create): JsonResponse
    {
        $value = $create->handle($request->toDTO());

        return AttributeValueResource::make($value)
            ->response()
            ->setStatusCode(201);
    }

    public function show(int $valueId): AttributeValueResource
    {
        return AttributeValueResource::make(
            AttributeValue::query()->findOrFail($valueId),
        );
    }

    public function update(
        UpdateAttributeValueRequest $request,
        int $valueId,
        UpdateAttributeValue $update,
    ): AttributeValueResource {
        return AttributeValueResource::make($update->handle($valueId, $request->toDTO()));
    }

    public function destroy(Request $request, DeleteAttributeValue $delete)
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
            'message' => 'Attribute value deleted successfully',
        ], Response::HTTP_OK);
    }
}
