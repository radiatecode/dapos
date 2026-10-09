<?php

namespace DA\Inventory\Enums;

enum PurchaseReturnRefundStatus: string
{
    case Pending = 'Pending';
    case Partial = 'Partial';
    case Refunded = 'Refunded';

    public function label(): string
    {
        return $this->value;
    }
}
