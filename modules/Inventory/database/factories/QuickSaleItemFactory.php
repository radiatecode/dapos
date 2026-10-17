<?php

namespace DA\Inventory\Database\Factories;

use DA\Inventory\Models\QuickSale;
use DA\Inventory\Models\QuickSaleItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuickSaleItem>
 */
class QuickSaleItemFactory extends Factory
{
    protected $model = QuickSaleItem::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'quick_sale_id' => QuickSale::factory(),
            'tenant_id' => fn (array $attributes): int => (int) QuickSale::withoutGlobalScopes()
                ->whereKey($attributes['quick_sale_id'])
                ->value('tenant_id'),
            'title' => fake()->words(2, true),
            'unit_amount' => '100.0000',
            'quantity' => '1.0000',
            'amount' => '100.0000',
            'sort_order' => 0,
        ];
    }
}
