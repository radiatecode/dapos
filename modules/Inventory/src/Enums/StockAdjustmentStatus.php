<?php

namespace DA\Inventory\Enums;

enum StockAdjustmentStatus: string
{
    case Draft = 'Draft';
    case Completed = 'Completed';
    case Cancelled = 'Cancelled';

    public function label(): string
    {
        return $this->value;
    }
}
