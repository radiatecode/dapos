<?php

namespace DA\Inventory\Database\Factories;

use DA\Admin\Models\Tenant;
use DA\Inventory\Enums\TaxType;
use DA\Inventory\Models\Tax;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tax>
 */
class TaxFactory extends Factory
{
    protected $model = Tax::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'tax_name' => fake()->unique()->words(2, true),
            'tax_type' => TaxType::PERCENTAGE->value,
            'tax_rate' => fake()->randomFloat(2, 1, 25),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }

    public function fixed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'tax_type' => TaxType::FIXED->value,
        ]);
    }
}
