<?php

namespace DA\Inventory\Enums;

enum BarcodeType: string
{
    case Ean = 'EAN';
    case Upc = 'UPC';
    case Code128 = 'CODE128';
    case Internal = 'INTERNAL';

    public function label(): string
    {
        return match ($this) {
            self::Ean => 'EAN',
            self::Upc => 'UPC',
            self::Code128 => 'CODE128',
            self::Internal => 'INTERNAL',
        };
    }
}
