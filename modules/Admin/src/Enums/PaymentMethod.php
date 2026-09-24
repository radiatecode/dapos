<?php

namespace DA\Admin\Enums;

enum PaymentMethod: string
{
    case Manual = 'MANUAL';
    case BankTransfer = 'BANK_TRANSFER';
    case Other = 'OTHER';

    public function label(): string
    {
        return match ($this) {
            self::Manual => 'Manual',
            self::BankTransfer => 'Bank transfer',
            self::Other => 'Other',
        };
    }
}
