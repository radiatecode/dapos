<?php

namespace DA\Inventory\Database\Factories;

use App\Enums\UserRole;
use App\Models\User;
use DA\Admin\Models\Tenant;
use DA\Inventory\Enums\PurchaseReturnRefundStatus;
use DA\Inventory\Enums\PurchaseReturnStatus;
use DA\Inventory\Models\Purchase;
use DA\Inventory\Models\PurchaseReturn;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseReturn>
 */
class PurchaseReturnFactory extends Factory
{
    protected $model = PurchaseReturn::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'purchase_id' => function (array $attributes): int {
                return Purchase::withoutEvents(
                    fn () => Purchase::factory()->create(['tenant_id' => $attributes['tenant_id']])->id,
                );
            },
            'store_id' => function (array $attributes): int {
                return (int) Purchase::query()->withoutGlobalScopes()->findOrFail($attributes['purchase_id'])->store_id;
            },
            'supplier_id' => function (array $attributes): int {
                return (int) Purchase::query()->withoutGlobalScopes()->findOrFail($attributes['purchase_id'])->supplier_id;
            },
            'return_number' => 'PR-'.fake()->unique()->numerify('########'),
            'po_number' => function (array $attributes): string {
                return (string) Purchase::query()->withoutGlobalScopes()->findOrFail($attributes['purchase_id'])->po_number;
            },
            'return_date' => now()->toDateString(),
            'total_return_qty' => '1.0000',
            'subtotal' => '10.0000',
            'tax_amount' => '0.0000',
            'discount_amount' => '0.0000',
            'grand_total' => '10.0000',
            'reason' => null,
            'notes' => null,
            'approval_by' => null,
            'approval_at' => null,
            'status' => PurchaseReturnStatus::Draft,
            'refund_status' => PurchaseReturnRefundStatus::Pending,
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

    public function pending(): static
    {
        return $this->state(fn (): array => [
            'status' => PurchaseReturnStatus::Pending,
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => PurchaseReturnStatus::Approved,
            'approval_by' => $attributes['created_by'],
            'approval_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => PurchaseReturnStatus::Reject,
            'approval_by' => $attributes['created_by'],
            'approval_at' => now(),
        ]);
    }
}
