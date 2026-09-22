<?php

namespace DA\Admin\Enums;

enum InvoiceItemType: string
{
    case Plan = 'PLAN';
    case Addon = 'ADDON';
    case Coupon = 'COUPON';

    public function label(): string
    {
        return match ($this) {
            self::Plan => 'Plan',
            self::Addon => 'Add-on',
            self::Coupon => 'Coupon',
        };
    }
}
