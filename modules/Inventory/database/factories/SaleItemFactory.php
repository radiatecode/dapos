<?php

namespace DA\Inventory\Database\Factories;

use DA\Inventory\Models\ProductVariant;
use DA\Inventory\Models\Sale;
use DA\Inventory\Models\SaleItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SaleItem>
 */
class SaleItemFactory extends Factory
{
    protected $model = SaleItem::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sale_id' => Sale::factory(),
            'tenant_id' => function (array $attributes): int {
                return Sale::query()
                    ->withoutGlobalScopes()
                    ->findOrFail($attributes['sale_id'])
                    ->tenant_id;
            },
            'product_variant_id' => ProductVariant::factory(),
            'product_variant_name' => fake()->words(2, true),
            'quantity' => '1.0000',
            'unit_price' => '15.0000',
            'discount_amount' => '0.0000',
            'tax_amount' => '0.0000',
            'subtotal' => '15.0000',
            'total' => '15.0000',
            'cost_price' => '10.0000',
            'cogs' => '10.0000',
        ];
    }
}
