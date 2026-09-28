<?php

namespace DA\Inventory\Database\Factories;

use DA\Admin\Models\Tenant;
use DA\Inventory\Enums\AllocationSourceType;
use DA\Inventory\Models\InventoryAllocation;
use DA\Inventory\Models\InventoryLayer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryAllocation>
 */
class InventoryAllocationFactory extends Factory
{
    protected $model = InventoryAllocation::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'inventory_layer_id' => fn (array $attributes): int => InventoryLayer::factory()->create([
                'tenant_id' => $attributes['tenant_id'],
            ])->id,
            'store_id' => fn (array $attributes): int => InventoryLayer::query()
                ->withoutGlobalScopes()
                ->findOrFail($attributes['inventory_layer_id'])
                ->store_id,
            'product_variant_id' => fn (array $attributes): int => InventoryLayer::query()
                ->withoutGlobalScopes()
                ->findOrFail($attributes['inventory_layer_id'])
                ->product_variant_id,
            'source_type' => AllocationSourceType::StockAdjustment,
            'source_id' => 1,
            'source_item_id' => null,
            'quantity' => '1.0000',
            'unit_cost' => '5.0000',
            'total_cost' => '5.0000',
        ];
    }
}
