<?php

namespace DA\Inventory\Database\Factories;

use DA\Inventory\Models\ProductVariant;
use DA\Inventory\Models\PurchaseOrderItem;
use DA\Inventory\Models\PurchaseReturn;
use DA\Inventory\Models\PurchaseReturnItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseReturnItem>
 */
class PurchaseReturnItemFactory extends Factory
{
    protected $model = PurchaseReturnItem::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'purchase_return_id' => PurchaseReturn::factory(),
            'tenant_id' => fn (array $attributes): int => (int) PurchaseReturn::query()
                ->withoutGlobalScopes()
                ->findOrFail($attributes['purchase_return_id'])
                ->tenant_id,
            'purchase_order_item_id' => function (array $attributes): int {
                $return = PurchaseReturn::query()->withoutGlobalScopes()->findOrFail($attributes['purchase_return_id']);

                return PurchaseOrderItem::factory()->create([
                    'purchase_order_id' => $return->purchase_id,
                    'product_variant_id' => ProductVariant::factory()->create([
                        'tenant_id' => $return->tenant_id,
                    ])->id,
                ])->id;
            },
            'product_variant_id' => fn (array $attributes): int => (int) PurchaseOrderItem::query()
                ->findOrFail($attributes['purchase_order_item_id'])
                ->product_variant_id,
            'quantity' => '1.0000',
            'unit_cost' => '10.0000',
            'tax_amount' => '0.0000',
            'discount_amount' => '0.0000',
            'subtotal' => '10.0000',
            'total_amount' => '10.0000',
        ];
    }
}
