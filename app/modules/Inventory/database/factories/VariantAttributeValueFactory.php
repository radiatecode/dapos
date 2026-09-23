<?php

namespace DA\Inventory\Database\Factories;

use DA\Inventory\Models\Attribute;
use DA\Inventory\Models\AttributeValue;
use DA\Inventory\Models\ProductVariant;
use DA\Inventory\Models\VariantAttributeValue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VariantAttributeValue>
 */
class VariantAttributeValueFactory extends Factory
{
    protected $model = VariantAttributeValue::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'variant_id' => ProductVariant::factory(),
            'attribute_id' => Attribute::factory(),
            'attribute_value_id' => fn (array $attributes): int => AttributeValue::factory()->create([
                'attribute_id' => $attributes['attribute_id'],
            ])->id,
        ];
    }
}
