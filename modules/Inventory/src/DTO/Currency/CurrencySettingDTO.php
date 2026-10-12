<?php

namespace DA\Inventory\DTO\Currency;

use DA\Inventory\Enums\DecimalSeparator;
use DA\Inventory\Enums\SymbolPosition;
use DA\Inventory\Enums\ThousandsSeparator;
use Spatie\LaravelData\Data;

class CurrencySettingDTO extends Data
{
    public function __construct(
        public string $currencyCode,
        public string $currencySymbol,
        public int $decimalPlaces,
        public DecimalSeparator $decimalSeparator,
        public ThousandsSeparator $thousandsSeparator,
        public SymbolPosition $symbolPosition,
        public bool $showDecimals,
        public bool $addSpace,
    ) {}
}
