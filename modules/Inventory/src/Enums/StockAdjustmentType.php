<?php

namespace DA\Inventory\Enums;

enum StockAdjustmentType: string
{
    case Addition = 'Addition';
    case Subtraction = 'Subtraction';

    public function label(): string
    {
        return $this->value;
    }
}
