<?php

namespace DA\Inventory\Database\Factories;

use App\Enums\UserRole;
use App\Models\User;
use DA\Admin\Models\Tenant;
use DA\Inventory\Enums\PurchasePaymentStatus;
use DA\Inventory\Enums\PurchaseStatus;
use DA\Inventory\Models\Purchase;
use DA\Inventory\Models\Store;
use DA\Inventory\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Purchase>
 */
class PurchaseFactory extends Factory
{
    protected $model = Purchase::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'supplier_id' => function (array $attributes): int {
                return Supplier::withoutEvents(
                    fn () => Supplier::factory()->create(['tenant_id' => $attributes['tenant_id']])->id,
                );
            },
            'store_id' => function (array $attributes): int {
                return Store::withoutEvents(
                    fn () => Store::factory()->create(['tenant_id' => $attributes['tenant_id']])->id,
                );
            },
            'po_number' => 'PO-'.fake()->unique()->numerify('######'),
            'order_date' => now()->toDateString(),
            'currency' => 'BDT',
            'subtotal' => '10.0000',
            'discount_amount' => '0.0000',
            'tax_amount' => '0.0000',
            'shipping_amount' => '0.0000',
            'grand_total' => '10.0000',
            'status' => PurchaseStatus::Pending,
            'payment_status' => PurchasePaymentStatus::Unpaid,
            'notes' => null,
            'created_by' => function (array $attributes): int {
                return User::factory()->create([
                    'tenant_id' => $attributes['tenant_id'],
                    'role' => UserRole::TenantUser,
                ])->id;
            },
            'updated_by' => function (array $attributes): int {
                return $attributes['created_by'];
            },
        ];
    }

    public function received(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => PurchaseStatus::Received,
        ]);
    }

    public function ordered(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => PurchaseStatus::Ordered,
        ]);
    }
}
