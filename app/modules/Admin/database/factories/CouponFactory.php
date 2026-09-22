<?php

namespace DA\Admin\Database\Factories;

use DA\Admin\Enums\CouponDiscountType;
use DA\Admin\Models\Coupon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Coupon>
 */
class CouponFactory extends Factory
{
    protected $model = Coupon::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => Str::upper(fake()->unique()->bothify('SAVE##')),
            'name' => 'Launch discount',
            'description' => fake()->sentence(),
            'discount_type' => CouponDiscountType::Percentage,
            'discount_value' => '10.00',
            'currency_id' => null,
            'max_redemptions' => null,
            'max_redemptions_per_tenant' => 1,
            'minimum_amount' => null,
            'starts_at' => null,
            'ends_at' => null,
            'is_active' => true,
        ];
    }

    public function percentage(string $value = '10.00'): static
    {
        return $this->state(fn (array $attributes): array => [
            'discount_type' => CouponDiscountType::Percentage,
            'discount_value' => $value,
            'currency_id' => null,
        ]);
    }

    public function fixed(string $value = '5.00'): static
    {
        return $this->state(fn (array $attributes): array => [
            'discount_type' => CouponDiscountType::Fixed,
            'discount_value' => $value,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes): array => [
            'starts_at' => now()->subMonth(),
            'ends_at' => now()->subDay(),
        ]);
    }
}
