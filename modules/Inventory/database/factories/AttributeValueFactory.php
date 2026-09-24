<?php

namespace DA\Inventory\Database\Factories;

use DA\Inventory\Models\Attribute;
use DA\Inventory\Models\AttributeValue;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<AttributeValue>
 */
class AttributeValueFactory extends Factory
{
    protected $model = AttributeValue::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $value = fake()->unique()->word();

        return [
            'attribute_id' => Attribute::factory(),
            'tenant_id' => fn (array $attributes) => Attribute::query()
                ->withoutGlobalScopes()
                ->findOrFail($attributes['attribute_id'])
                ->tenant_id,
            'value' => $value,
            'slug' => Str::slug($value).'-'.fake()->unique()->numerify('###'),
            'code' => null,
            'sort_order' => 0,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }
}
