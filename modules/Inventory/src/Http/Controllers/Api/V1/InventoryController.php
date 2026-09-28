<?php

namespace DA\Inventory\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use DA\Inventory\Http\Resources\Api\V1\InventoryBalanceResource;
use DA\Inventory\Models\InventoryBalance;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class InventoryController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = validator([
            'per_page' => $request->input('per_page', $request->input('limit', 15)),
            'store_id' => $request->filled('store_id') ? $request->input('store_id') : null,
            'product_id' => $request->filled('product_id') ? $request->input('product_id') : null,
            'product_variant_id' => $request->filled('product_variant_id') ? $request->input('product_variant_id') : null,
        ], [
            'per_page' => ['required', 'integer', 'min:1', 'max:100'],
            'store_id' => ['nullable', 'integer'],
            'product_id' => ['nullable', 'integer'],
            'product_variant_id' => ['nullable', 'integer'],
        ])->validate();

        return InventoryBalanceResource::collection(
            InventoryBalance::queries()->paginateForTenant(
                (int) $filters['per_page'],
                isset($filters['store_id']) ? (int) $filters['store_id'] : null,
                isset($filters['product_id']) ? (int) $filters['product_id'] : null,
                isset($filters['product_variant_id']) ? (int) $filters['product_variant_id'] : null,
            ),
        );
    }
}
