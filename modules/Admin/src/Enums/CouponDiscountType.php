<?php

namespace DA\Admin\Enums;

enum CouponDiscountType: string
{
    case Percentage = 'PERCENTAGE';
    case Fixed = 'FIXED';

    public function label(): string
    {
        return match ($this) {
            self::Percentage => 'Percentage',
            self::Fixed => 'Fixed amount',
        };
    }
}
