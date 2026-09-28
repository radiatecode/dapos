<?php

namespace DA\Inventory\Database\Factories;

use DA\Inventory\Models\ProductVariant;
use DA\Inventory\Models\Purchase;
use DA\Inventory\Models\PurchaseOrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseOrderItem>
 */
class PurchaseOrderItemFactory extends Factory
{
    protected $model = PurchaseOrderItem::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'purchase_order_id' => Purchase::factory(),
            'product_variant_id' => ProductVariant::factory(),
            'quantity' => '1.0000',
            'unit_cost' => '10.0000',
            'discount_amount' => '0.0000',
            'tax_amount' => '0.0000',
            'total' => '10.0000',
        ];
    }
}
