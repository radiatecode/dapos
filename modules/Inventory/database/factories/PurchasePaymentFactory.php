<?php

namespace DA\Inventory\Database\Factories;

use App\Models\User;
use DA\Inventory\Models\Purchase;
use DA\Inventory\Models\PurchasePayment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchasePayment>
 */
class PurchasePaymentFactory extends Factory
{
    protected $model = PurchasePayment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => function (array $attributes): int {
                return (int) Purchase::withoutTenantScope()->findOrFail($attributes['purchase_id'])->tenant_id;
            },
            'purchase_id' => Purchase::factory(),
            'amount' => '1.0000',
            'reference_number' => null,
            'note' => null,
            'paid_at' => now(),
            'recorded_by' => User::factory(),
        ];
    }
}
