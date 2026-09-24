<?php

namespace DA\Admin\Database\Factories;

use DA\Admin\Models\Currency;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Currency>
 */
class CurrencyFactory extends Factory
{
    protected $model = Currency::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('??#')),
            'name' => fake()->unique()->bothify('Currency ??#'),
            'symbol' => '$',
            'is_active' => true,
        ];
    }

    public function usd(): static
    {
        return $this->state(fn (array $attributes): array => [
            'code' => 'USD',
            'name' => 'US Dollar',
            'symbol' => '$',
        ]);
    }
}
