<?php

namespace DA\Inventory\Database\Factories;

use App\Enums\UserRole;
use App\Models\User;
use DA\Admin\Models\Tenant;
use DA\Inventory\Enums\PaymentMethod;
use DA\Inventory\Models\QuickSale;
use DA\Inventory\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuickSale>
 */
class QuickSaleFactory extends Factory
{
    protected $model = QuickSale::class;

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
            'invoice_number' => 'QS-'.fake()->unique()->numerify('########'),
            'sale_date' => now()->toDateString(),
            'total_items' => 1,
            'grand_total' => '100.0000',
            'payment_method' => PaymentMethod::CASH,
            'received_amount' => '100.0000',
            'change_amount' => '0.0000',
            'reference_number' => null,
            'note' => null,
            'cashier_id' => function (array $attributes): int {
                return User::factory()->create([
                    'tenant_id' => $attributes['tenant_id'],
                    'role' => UserRole::TenantUser,
                ])->id;
            },
        ];
    }
}
