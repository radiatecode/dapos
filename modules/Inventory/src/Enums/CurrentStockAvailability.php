<?php

namespace DA\Inventory\Enums;

enum CurrentStockAvailability: string
{
    case Available = 'available';
    case Reserved = 'reserved';

    public function label(): string
    {
        return match ($this) {
            self::Available => 'Available to sell',
            self::Reserved => 'Reserved only',
        };
    }
}
