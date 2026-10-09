<?php

namespace DA\Inventory\Enums;

enum TaxMode: int
{
    case ORDER_LEVEL = 1;
    case ITEM_LEVEL = 2;

    public function label(): string
    {
        return match ($this) {
            self::ORDER_LEVEL => 'Order-Level Tax',
            self::ITEM_LEVEL => 'Item-Level Tax',
        };
    }
}
