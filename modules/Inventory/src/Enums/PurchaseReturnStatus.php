<?php

namespace DA\Inventory\Enums;

enum PurchaseReturnStatus: string
{
    case Draft = 'Draft';
    case Pending = 'Pending';
    case Approved = 'Approved';
    case Reject = 'Reject';

    public function label(): string
    {
        return $this->value;
    }

    public function isOpen(): bool
    {
        return $this === self::Draft || $this === self::Pending;
    }
}
