<?php

namespace DA\Inventory\Enums;

enum UnitType: string
{
    case Quantity = 'quantity';
    case Weight = 'weight';
    case Volume = 'volume';
    case Length = 'length';

    public function label(): string
    {
        return match ($this) {
            self::Quantity => 'Quantity',
            self::Weight => 'Weight',
            self::Volume => 'Volume',
            self::Length => 'Length',
        };
    }
}
