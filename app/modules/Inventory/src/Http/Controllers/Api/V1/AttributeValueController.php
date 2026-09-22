<?php

namespace DA\Inventory\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use DA\Inventory\Actions\AttributeValue\CreateAttributeValue;
use DA\Inventory\Actions\AttributeValue\DeleteAttributeValue;
use DA\Inventory\Actions\AttributeValue\UpdateAttributeValue;
use DA\Inventory\Http\Requests\Api\V1\AttributeValue\StoreAttributeValueRequest;
use DA\Inventory\Http\Requests\Api\V1\AttributeValue\UpdateAttributeValueRequest;
use DA\Inventory\Http\Resources\Api\V1\AttributeValueResource;
use DA\Inventory\Models\Attribute;
use DA\Inventory\Models\AttributeValue;
use DA\Inventory\Services\AttributeValueService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class AttributeValueController extends Controller
{
    public function index(Request $request, Attribute $attribute, AttributeValueService $values): AnonymousResourceCollection
    {
        return AttributeValueResource::collection(
            $values->paginate($attribute, $request->integer('per_page', 15)),
        );
    }

    public function store(StoreAttributeValueRequest $request, Attribute $attribute, CreateAttributeValue $create): JsonResponse
    {
        $value = $create->handle($attribute, $request->toDTO());

        return AttributeValueResource::make($value)
            ->response()
            ->setStatusCode(201);
    }

    public function show(Attribute $attribute, AttributeValue $attributeValue, AttributeValueService $values): AttributeValueResource
    {
        return AttributeValueResource::make($values->show($attributeValue));
    }

    public function update(
        UpdateAttributeValueRequest $request,
        Attribute $attribute,
        AttributeValue $attributeValue,
        UpdateAttributeValue $update,
    ): AttributeValueResource {
        return AttributeValueResource::make($update->handle($attributeValue, $request->toDTO()));
    }

    public function destroy(Attribute $attribute, AttributeValue $attributeValue, DeleteAttributeValue $delete): Response
    {
        $this->authorize('delete', $attributeValue);

        $delete->handle($attributeValue);

        return response()->noContent();
    }
}
