<?php

namespace DA\Inventory\Database\Factories;

use DA\Inventory\Models\Attribute;
use DA\Inventory\Models\Product;
use DA\Inventory\Models\ProductAttribute;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductAttribute>
 */
class ProductAttributeFactory extends Factory
{
    protected $model = ProductAttribute::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'attribute_id' => Attribute::factory(),
            'sort_order' => 0,
            'is_required' => true,
        ];
    }
}
