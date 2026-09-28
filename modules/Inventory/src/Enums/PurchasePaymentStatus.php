<?php

namespace DA\Inventory\Enums;

enum PurchasePaymentStatus: string
{
    case Paid = 'Paid';
    case Unpaid = 'Unpaid';
    case Partial = 'Partial';

    public function label(): string
    {
        return $this->value;
    }
}
