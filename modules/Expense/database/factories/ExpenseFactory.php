<?php

namespace DA\Expense\Database\Factories;

use DA\Admin\Models\Tenant;
use DA\Expense\Enums\ExpenseStatus;
use DA\Expense\Models\Expense;
use DA\Expense\Models\ExpenseCategory;
use DA\Inventory\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Expense>
 */
class ExpenseFactory extends Factory
{
    protected $model = Expense::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'category_id' => function (array $attributes): int {
                return ExpenseCategory::withoutEvents(
                    fn () => ExpenseCategory::factory()->create(['tenant_id' => $attributes['tenant_id']])->id,
                );
            },
            'store_id' => function (array $attributes): int {
                return Store::withoutEvents(
                    fn () => Store::factory()->create(['tenant_id' => $attributes['tenant_id']])->id,
                );
            },
            'description' => fake()->sentence(),
            'amount' => number_format(fake()->randomFloat(2, 1, 1000), 4, '.', ''),
            'expense_date' => fake()->date(),
            'attachment' => null,
            'status' => ExpenseStatus::Pending,
            'approval_time' => null,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ExpenseStatus::Approved,
            'approval_time' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ExpenseStatus::Rejected,
            'approval_time' => now(),
        ]);
    }
}
