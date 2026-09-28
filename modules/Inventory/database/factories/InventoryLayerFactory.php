<?php

namespace DA\Inventory\Database\Factories;

use DA\Admin\Models\Tenant;
use DA\Inventory\Enums\InventoryLayerSourceType;
use DA\Inventory\Enums\InventoryLayerStatus;
use DA\Inventory\Models\InventoryLayer;
use DA\Inventory\Models\ProductVariant;
use DA\Inventory\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryLayer>
 */
class InventoryLayerFactory extends Factory
{
    protected $model = InventoryLayer::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'store_id' => fn (array $attributes): int => Store::factory()->create([
                'tenant_id' => $attributes['tenant_id'],
            ])->id,
            'product_variant_id' => fn (array $attributes): int => ProductVariant::factory()->create([
                'tenant_id' => $attributes['tenant_id'],
            ])->id,
            'supplier_id' => null,
            'source_type' => InventoryLayerSourceType::OpeningStock,
            'source_id' => 1,
            'source_item_id' => null,
            'received_qty' => '10.0000',
            'remaining_qty' => '10.0000',
            'unit_cost' => '5.0000',
            'total_cost' => '50.0000',
            'received_at' => now(),
            'batch_id' => null,
            'expiry_date' => null,
            'status' => InventoryLayerStatus::Open,
        ];
    }
}
