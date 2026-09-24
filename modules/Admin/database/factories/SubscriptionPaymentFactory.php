<?php

namespace DA\Admin\Database\Factories;

use DA\Admin\Enums\PaymentMethod;
use DA\Admin\Enums\PaymentStatus;
use DA\Admin\Models\Currency;
use DA\Admin\Models\Invoice;
use DA\Admin\Models\SubscriptionPayment;
use DA\Admin\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SubscriptionPayment>
 */
class SubscriptionPaymentFactory extends Factory
{
    protected $model = SubscriptionPayment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'invoice_id' => Invoice::factory(),
            'amount' => '19.00',
            'currency_id' => Currency::factory(),
            'payment_method' => PaymentMethod::Manual,
            'transaction_id' => 'manual_'.fake()->unique()->numerify('######'),
            'status' => PaymentStatus::Succeeded,
            'paid_at' => now(),
        ];
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => PaymentStatus::Failed,
            'paid_at' => null,
        ]);
    }
}
