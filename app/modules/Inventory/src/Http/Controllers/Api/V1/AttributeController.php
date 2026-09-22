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
use DA\Inventory\Services\AttributeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class AttributeController extends Controller
{
    public function index(Request $request, AttributeService $attributes): AnonymousResourceCollection
    {
        return AttributeResource::collection(
            $attributes->paginate($request->integer('per_page', 15)),
        );
    }

    public function store(StoreAttributeRequest $request, CreateAttribute $create): JsonResponse
    {
        $attribute = $create->handle($request->toDTO());

        return AttributeResource::make($attribute)
            ->response()
            ->setStatusCode(201);
    }

    public function show(Attribute $attribute, AttributeService $attributes): AttributeResource
    {
        return AttributeResource::make($attributes->show($attribute));
    }

    public function update(UpdateAttributeRequest $request, Attribute $attribute, UpdateAttribute $update): AttributeResource
    {
        return AttributeResource::make($update->handle($attribute, $request->toDTO()));
    }

    public function destroy(Attribute $attribute, DeleteAttribute $delete): Response
    {
        $this->authorize('delete', $attribute);

        $delete->handle($attribute);

        return response()->noContent();
    }
}
