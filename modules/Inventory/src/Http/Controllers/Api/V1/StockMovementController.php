<?php

namespace DA\Inventory\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use DA\Inventory\Enums\StockDirection;
use DA\Inventory\Enums\StockMovementType;
use DA\Inventory\Http\Resources\Api\V1\StockMovementResource;
use DA\Inventory\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class StockMovementController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = validator([
            'per_page' => $request->input('per_page', $request->input('limit', 15)),
            'store_id' => $request->filled('store_id') ? $request->input('store_id') : null,
            'product_variant_id' => $request->filled('product_variant_id') ? $request->input('product_variant_id') : null,
            'movement_type' => $request->filled('movement_type') ? $request->input('movement_type') : null,
            'direction' => $request->filled('direction') ? $request->input('direction') : null,
            'date_from' => $request->filled('date_from') ? $request->input('date_from') : null,
            'date_to' => $request->filled('date_to') ? $request->input('date_to') : null,
        ], [
            'per_page' => ['required', 'integer', 'min:1', 'max:100'],
            'store_id' => ['nullable', 'integer'],
            'product_variant_id' => ['nullable', 'integer'],
            'movement_type' => ['nullable', Rule::enum(StockMovementType::class)],
            'direction' => ['nullable', Rule::enum(StockDirection::class)],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
        ])->validate();

        return StockMovementResource::collection(
            StockMovement::queries()->paginateForTenant(
                (int) $filters['per_page'],
                isset($filters['store_id']) ? (int) $filters['store_id'] : null,
                isset($filters['product_variant_id']) ? (int) $filters['product_variant_id'] : null,
                isset($filters['movement_type']) ? StockMovementType::from($filters['movement_type']) : null,
                isset($filters['direction']) ? StockDirection::from($filters['direction']) : null,
                $filters['date_from'] ?? null,
                $filters['date_to'] ?? null,
            ),
        );
    }
}
