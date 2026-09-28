<?php

namespace DA\Inventory\Database\Factories;

use DA\Admin\Models\Tenant;
use DA\Inventory\Models\InventoryBalance;
use DA\Inventory\Models\ProductVariant;
use DA\Inventory\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryBalance>
 */
class InventoryBalanceFactory extends Factory
{
    protected $model = InventoryBalance::class;

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
            'quantity' => '10.0000',
            'reserved_quantity' => '0.0000',
            'available_quantity' => '10.0000',
            'stock_value' => '50.0000',
        ];
    }
}
