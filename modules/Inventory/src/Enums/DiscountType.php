<?php

namespace DA\Inventory\Enums;

enum DiscountType: int
{
    case FIXED = 1;
    case PERCENTAGE = 2;

    public function label(): string
    {
        return match ($this) {
            self::FIXED => 'Fixed',
            self::PERCENTAGE => 'Percentage',
        };
    }
}
