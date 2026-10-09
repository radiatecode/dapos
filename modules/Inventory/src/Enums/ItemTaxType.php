<?php

namespace DA\Inventory\Enums;

enum ItemTaxType: int
{
    case INCLUSIVE = 1;
    case EXCLUSIVE = 2;

    public function label(): string
    {
        return match ($this) {
            self::INCLUSIVE => 'Inclusive Tax',
            self::EXCLUSIVE => 'Exclusive Tax',
        };
    }
}
