<?php

namespace DA\Inventory\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use DA\Inventory\Enums\InventoryLayerSourceType;
use DA\Inventory\Enums\InventoryLayerStatus;
use DA\Inventory\Http\Resources\Api\V1\InventoryLayerResource;
use DA\Inventory\Models\InventoryLayer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class InventoryLayerController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = validator([
            'per_page' => $request->input('per_page', $request->input('limit', 15)),
            'store_id' => $request->filled('store_id') ? $request->input('store_id') : null,
            'product_variant_id' => $request->filled('product_variant_id') ? $request->input('product_variant_id') : null,
            'status' => $request->filled('status') ? $request->input('status') : null,
            'source_type' => $request->filled('source_type') ? $request->input('source_type') : null,
        ], [
            'per_page' => ['required', 'integer', 'min:1', 'max:100'],
            'store_id' => ['nullable', 'integer'],
            'product_variant_id' => ['nullable', 'integer'],
            'status' => ['nullable', Rule::enum(InventoryLayerStatus::class)],
            'source_type' => ['nullable', Rule::enum(InventoryLayerSourceType::class)],
        ])->validate();

        return InventoryLayerResource::collection(
            InventoryLayer::queries()->paginateForTenant(
                (int) $filters['per_page'],
                isset($filters['store_id']) ? (int) $filters['store_id'] : null,
                isset($filters['product_variant_id']) ? (int) $filters['product_variant_id'] : null,
                isset($filters['status']) ? InventoryLayerStatus::from($filters['status']) : null,
                isset($filters['source_type']) ? InventoryLayerSourceType::from($filters['source_type']) : null,
            ),
        );
    }
}
