<?php

namespace DA\Inventory\Database\Factories;

use App\Models\User;
use DA\Admin\Models\Tenant;
use DA\Inventory\Enums\StockAdjustmentStatus;
use DA\Inventory\Enums\StockAdjustmentType;
use DA\Inventory\Models\ProductVariant;
use DA\Inventory\Models\StockAdjustment;
use DA\Inventory\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockAdjustment>
 */
class StockAdjustmentFactory extends Factory
{
    protected $model = StockAdjustment::class;

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
            'reference_number' => 'ADJ-'.fake()->unique()->numerify('######'),
            'adjustment_date' => now()->toDateString(),
            'adjustment_type' => StockAdjustmentType::Addition,
            'reason' => 'Count correction',
            'status' => StockAdjustmentStatus::Draft,
            'notes' => null,
            'created_by' => fn (array $attributes): int => User::factory()->create([
                'tenant_id' => $attributes['tenant_id'],
            ])->id,
            'updated_by' => fn (array $attributes): int => $attributes['created_by'],
            'system_quantity' => null,
            'adjusted_quantity' => '1.0000',
            'difference_quantity' => null,
            'unit_cost' => '5.0000',
            'total_cost' => null,
        ];
    }
}
