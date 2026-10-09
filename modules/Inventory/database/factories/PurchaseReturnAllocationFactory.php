<?php

namespace DA\Inventory\Database\Factories;

use DA\Inventory\Models\InventoryLayer;
use DA\Inventory\Models\PurchaseReturnAllocation;
use DA\Inventory\Models\PurchaseReturnItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseReturnAllocation>
 */
class PurchaseReturnAllocationFactory extends Factory
{
    protected $model = PurchaseReturnAllocation::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'purchase_return_item_id' => PurchaseReturnItem::factory(),
            'tenant_id' => fn (array $attributes): int => (int) PurchaseReturnItem::query()
                ->findOrFail($attributes['purchase_return_item_id'])
                ->tenant_id,
            'product_variant_id' => fn (array $attributes): int => (int) PurchaseReturnItem::query()
                ->findOrFail($attributes['purchase_return_item_id'])
                ->product_variant_id,
            'inventory_layer_id' => fn (array $attributes): int => InventoryLayer::factory()->create([
                'tenant_id' => $attributes['tenant_id'],
                'product_variant_id' => $attributes['product_variant_id'],
            ])->id,
            'quantity' => '1.0000',
            'unit_cost' => '10.0000',
            'total_cost' => '10.0000',
        ];
    }
}
