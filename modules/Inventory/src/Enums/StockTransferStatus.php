<?php

namespace DA\Inventory\Enums;

enum StockTransferStatus: string
{
    case Draft = 'Draft';
    case Pending = 'Pending';
    case Completed = 'Completed';
    case Cancelled = 'Cancelled';

    public function label(): string
    {
        return $this->value;
    }
}
