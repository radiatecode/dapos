<?php

namespace DA\Admin\Database\Factories;

use DA\Admin\Enums\InvoiceItemType;
use DA\Admin\Models\Invoice;
use DA\Admin\Models\InvoiceItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InvoiceItem>
 */
class InvoiceItemFactory extends Factory
{
    protected $model = InvoiceItem::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'invoice_id' => Invoice::factory(),
            'item_type' => InvoiceItemType::Plan,
            'description' => 'Starter',
            'quantity' => 1,
            'unit_price' => '19.00',
            'subtotal' => '19.00',
            'discount_amount' => '0.00',
            'tax_amount' => '0.00',
            'total_amount' => '19.00',
            'reference_type' => InvoiceItemType::Plan->value,
            'reference_id' => 1,
        ];
    }
}
