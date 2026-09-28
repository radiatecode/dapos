<?php

namespace DA\Inventory\Enums;

enum StockDirection: string
{
    case In = 'In';
    case Out = 'Out';

    public function label(): string
    {
        return $this->value;
    }
}
