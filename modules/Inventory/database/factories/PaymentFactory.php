<?php

namespace DA\Inventory\Database\Factories;

use DA\Inventory\Models\Payment;
use DA\Inventory\Models\Sale;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sale_id' => Sale::factory(),
            'tenant_id' => function (array $attributes): int {
                return Sale::query()
                    ->withoutGlobalScopes()
                    ->findOrFail($attributes['sale_id'])
                    ->tenant_id;
            },
            'customer_id' => null,
            'payment_method_id' => 1,
            'bill_amount' => '15.0000',
            'received_amount' => '15.0000',
            'paid_amount' => '15.0000',
            'due_amount' => '0.0000',
            'returned_amount' => '0.0000',
            'reference_number' => null,
            'paid_at' => now(),
            'received_by' => function (array $attributes): int {
                return (int) Sale::query()
                    ->withoutGlobalScopes()
                    ->findOrFail($attributes['sale_id'])
                    ->cashier_id;
            },
        ];
    }
}
