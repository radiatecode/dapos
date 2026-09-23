<?php

namespace DA\Inventory\Database\Factories;

use DA\Admin\Models\Tenant;
use DA\Inventory\Enums\ProductType;
use DA\Inventory\Enums\UnitType;
use DA\Inventory\Models\Brand;
use DA\Inventory\Models\Category;
use DA\Inventory\Models\Product;
use DA\Inventory\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);

        return [
            'tenant_id' => Tenant::factory(),
            'category_id' => fn (array $attributes): int => Category::withoutEvents(
                fn () => Category::factory()->create([
                    'tenant_id' => $attributes['tenant_id'],
                ])->id,
            ),
            'brand_id' => fn (array $attributes): int => Brand::withoutEvents(
                fn () => Brand::factory()->create([
                    'tenant_id' => $attributes['tenant_id'],
                ])->id,
            ),
            'unit_id' => function (array $attributes): int {
                return Unit::withoutEvents(function () use ($attributes): int {
                    $unit = new Unit;
                    $unit->tenant_id = $attributes['tenant_id'];
                    $unit->name = 'Piece';
                    $unit->code = 'PC'.fake()->unique()->numerify('####');
                    $unit->unit_type = UnitType::Quantity;
                    $unit->precision = 0;
                    $unit->is_active = true;
                    $unit->save();

                    return $unit->id;
                });
            },
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numerify('###'),
            'description' => fake()->sentence(),
            'image' => null,
            'product_type' => ProductType::Simple,
            'track_inventory' => true,
            'is_active' => true,
            'is_stock_out' => false,
            'manufacture_date' => null,
            'expire_date' => null,
            'warranty_in_days' => null,
            'guarantee_in_days' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }

    public function variable(): static
    {
        return $this->state(fn (array $attributes): array => [
            'product_type' => ProductType::Variable,
        ]);
    }
}
