<?php

namespace DA\Inventory\Services;

use DA\Inventory\Enums\DecimalSeparator;
use DA\Inventory\Enums\ThousandsSeparator;
use DA\Inventory\Models\CurrencySetting;

final class TenantNumberFormatter
{
    public function __construct(
        public readonly string $currencyCode,
        private readonly int $decimalPlaces = 2,
        private readonly DecimalSeparator $decimalSeparator = DecimalSeparator::DOT,
        private readonly ThousandsSeparator $thousandsSeparator = ThousandsSeparator::COMMA,
    ) {}

    public static function fromCurrencySetting(?CurrencySetting $setting, string $fallbackCurrencyCode): self
    {
        if (! $setting instanceof CurrencySetting) {
            return new self($fallbackCurrencyCode);
        }

        return new self(
            currencyCode: (string) $setting->currency_code,
            decimalPlaces: $setting->show_decimals ? (int) $setting->decimal_places : 0,
            decimalSeparator: $setting->decimal_separator,
            thousandsSeparator: $setting->thousands_separator,
        );
    }

    public function amount(mixed $value): string
    {
        return $this->format($value, $this->decimalPlaces);
    }

    public function quantity(mixed $value): string
    {
        $formatted = $this->format($value, Decimal::SCALE);
        $decimal = $this->decimalSeparator->character();

        return rtrim(rtrim($formatted, '0'), $decimal);
    }

    private function format(mixed $value, int $decimals): string
    {
        return number_format(
            (float) $value,
            $decimals,
            $this->decimalSeparator->character(),
            $this->thousandsSeparator->character(),
        );
    }
}
