<?php

namespace DA\Inventory\Database\Factories;

use DA\Inventory\Enums\BarcodeType;
use DA\Inventory\Models\Product;
use DA\Inventory\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    protected $model = ProductVariant::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'tenant_id' => fn (array $attributes): int => Product::query()
                ->withoutGlobalScopes()
                ->findOrFail($attributes['product_id'])
                ->tenant_id,
            'sku' => fake()->unique()->bothify('SKU-####'),
            'barcode' => fake()->unique()->numerify('#############'),
            'barcode_type' => BarcodeType::Ean,
            'name' => null,
            'cost_price' => '8.0000',
            'selling_price' => '12.0000',
            'compare_at_price' => null,
            'weight' => null,
            'quantity' => '10.0000',
            'min_stock_level' => 2,
            'image' => null,
            'is_default' => false,
            'is_active' => true,
        ];
    }

    public function default(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_default' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }
}
