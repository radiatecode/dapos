<?php

namespace DA\Inventory\Database\Factories;

use DA\Admin\Models\Currency;
use DA\Admin\Models\Tenant;
use DA\Inventory\Enums\DecimalSeparator;
use DA\Inventory\Enums\SymbolPosition;
use DA\Inventory\Enums\ThousandsSeparator;
use DA\Inventory\Models\CurrencySetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CurrencySetting>
 */
class CurrencySettingFactory extends Factory
{
    protected $model = CurrencySetting::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $currency = Currency::query()->firstOrCreate(
            ['code' => 'BDT'],
            [
                'name' => 'Bangladeshi Taka',
                'symbol' => '৳',
                'is_active' => true,
            ],
        );

        return [
            'tenant_id' => Tenant::factory(),
            'currency_code' => $currency->code,
            'currency_symbol' => $currency->symbol,
            'decimal_places' => 2,
            'decimal_separator' => DecimalSeparator::DOT,
            'thousands_separator' => ThousandsSeparator::COMMA,
            'symbol_position' => SymbolPosition::LEFT,
            'show_decimals' => true,
            'add_space' => false,
        ];
    }

    public function using(Currency $currency): static
    {
        return $this->state(fn (array $attributes): array => [
            'currency_code' => $currency->code,
            'currency_symbol' => $currency->symbol,
        ]);
    }
}
