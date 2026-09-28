<?php

namespace DA\Inventory\Database\Factories;

use App\Models\User;
use DA\Admin\Models\Tenant;
use DA\Inventory\Enums\StockTransferStatus;
use DA\Inventory\Models\ProductVariant;
use DA\Inventory\Models\StockTransfer;
use DA\Inventory\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockTransfer>
 */
class StockTransferFactory extends Factory
{
    protected $model = StockTransfer::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'product_variant_id' => fn (array $attributes): int => ProductVariant::factory()->create([
                'tenant_id' => $attributes['tenant_id'],
            ])->id,
            'reference_number' => 'TRF-'.fake()->unique()->numerify('######'),
            'from_store_id' => fn (array $attributes): int => Store::factory()->create([
                'tenant_id' => $attributes['tenant_id'],
            ])->id,
            'to_store_id' => fn (array $attributes): int => Store::factory()->create([
                'tenant_id' => $attributes['tenant_id'],
            ])->id,
            'transfer_date' => now()->toDateString(),
            'quantity' => '1.0000',
            'unit_cost' => null,
            'total_cost' => null,
            'status' => StockTransferStatus::Draft,
            'notes' => null,
            'created_by' => fn (array $attributes): int => User::factory()->create([
                'tenant_id' => $attributes['tenant_id'],
            ])->id,
        ];
    }
}
