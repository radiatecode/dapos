<?php

namespace DA\Inventory\Enums;

enum PurchaseStatus: string
{
    case Pending = 'Pending';
    case Received = 'Received';
    case Ordered = 'Ordered';

    public function label(): string
    {
        return $this->value;
    }
}
