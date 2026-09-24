<?php

namespace DA\Admin\Database\Factories;

use DA\Admin\Enums\InvoiceStatus;
use DA\Admin\Models\Currency;
use DA\Admin\Models\Invoice;
use DA\Admin\Models\Subscription;
use DA\Admin\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $periodStart = now()->startOfSecond();

        return [
            'tenant_id' => Tenant::factory(),
            'subscription_id' => Subscription::factory(),
            'invoice_number' => 'INV-'.now()->format('Ym').'-'.fake()->unique()->numerify('####'),
            'status' => InvoiceStatus::Open,
            'billing_period_start' => $periodStart,
            'billing_period_end' => $periodStart->copy()->addMonth(),
            'subtotal' => '19.00',
            'discount_amount' => '0.00',
            'tax_amount' => '0.00',
            'total_amount' => '19.00',
            'currency_id' => Currency::factory(),
            'due_date' => $periodStart->copy()->addMonth(),
            'paid_at' => null,
        ];
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => InvoiceStatus::Paid,
            'paid_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => InvoiceStatus::Failed,
            'paid_at' => null,
        ]);
    }
}
