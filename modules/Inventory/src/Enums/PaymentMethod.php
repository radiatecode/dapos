<?php

namespace DA\Inventory\Enums;

enum PaymentMethod: int
{
    case CASH = 1;
    case CARD = 2;
    case BANK_TRANSFER = 3;
    case MOBILE_BANKING = 4;
    case DUE = 5;

    public function label()
    {
        return match ($this) {
            self::CASH => 'Cash',
            self::CARD => 'Card',
            self::BANK_TRANSFER => 'Bank-Transfer',
            self::MOBILE_BANKING => 'Mobile-Banking',
            self::DUE => 'Due',
        };
    }
}
