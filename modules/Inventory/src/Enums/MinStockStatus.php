<?php

namespace DA\Inventory\Enums;

enum MinStockStatus: string
{
    case OutOfStock = 'out_of_stock';
    case LowStock = 'low_stock';

    public function label(): string
    {
        return match ($this) {
            self::OutOfStock => 'Out of stock',
            self::LowStock => 'Low stock',
        };
    }
}
