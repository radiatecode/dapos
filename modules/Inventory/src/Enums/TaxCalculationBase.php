<?php

namespace DA\Inventory\Enums;

enum TaxCalculationBase: int
{
    case INCLUDE_SERVICE_CHARGES = 1;
    case EXCLUDE_SERVICE_CHARGES = 2;

    public function label(): string
    {
        return match ($this) {
            self::INCLUDE_SERVICE_CHARGES => 'Include service charges in tax calculation',
            self::EXCLUDE_SERVICE_CHARGES => 'Exclude service charges from tax calculation',
        };
    }
}
