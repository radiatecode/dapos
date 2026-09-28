<?php

namespace DA\Inventory\Database\Factories;

use App\Models\User;
use DA\Admin\Models\Tenant;
use DA\Inventory\Enums\StockDirection;
use DA\Inventory\Enums\StockMovementType;
use DA\Inventory\Enums\StockReferenceType;
use DA\Inventory\Models\InventoryLayer;
use DA\Inventory\Models\ProductVariant;
use DA\Inventory\Models\StockMovement;
use DA\Inventory\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockMovement>
 */
class StockMovementFactory extends Factory
{
    protected $model = StockMovement::class;

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
            'movement_type' => StockMovementType::OpeningStock,
            'quantity' => '10.0000',
            'unit_cost' => '5.0000',
            'reference_type' => StockReferenceType::Product,
            'reference_id' => 1,
            'reference_item_id' => 1,
            'inventory_layer_id' => fn (array $attributes): int => InventoryLayer::factory()->create([
                'tenant_id' => $attributes['tenant_id'],
                'store_id' => $attributes['store_id'],
                'product_variant_id' => $attributes['product_variant_id'],
            ])->id,
            'batch_id' => null,
            'serial_id' => null,
            'direction' => StockDirection::In,
            'occurred_at' => now(),
            'created_by' => fn (array $attributes): int => User::factory()->create([
                'tenant_id' => $attributes['tenant_id'],
            ])->id,
        ];
    }
}
