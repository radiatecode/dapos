<?php

namespace DA\Inventory\Database\Factories;

use App\Enums\UserRole;
use App\Models\User;
use DA\Admin\Models\Tenant;
use DA\Inventory\Enums\SalePaymentStatus;
use DA\Inventory\Enums\SaleStatus;
use DA\Inventory\Models\Sale;
use DA\Inventory\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sale>
 */
class SaleFactory extends Factory
{
    protected $model = Sale::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'store_id' => function (array $attributes): int {
                return Store::withoutEvents(
                    fn () => Store::factory()->create(['tenant_id' => $attributes['tenant_id']])->id,
                );
            },
            'customer_id' => null,
            'invoice_number' => 'INV-'.fake()->unique()->numerify('########'),
            'sale_date' => now()->toDateString(),
            'total_items' => '1.0000',
            'subtotal' => '15.0000',
            'discount_amount' => '0.0000',
            'tax_amount' => '0.0000',
            'grand_total' => '15.0000',
            'paid_amount' => '15.0000',
            'due_amount' => '0.0000',
            'status' => SaleStatus::Completed,
            'payment_status' => SalePaymentStatus::Paid,
            'cashier_id' => function (array $attributes): int {
                return User::factory()->create([
                    'tenant_id' => $attributes['tenant_id'],
                    'role' => UserRole::TenantUser,
                ])->id;
            },
        ];
    }
}
