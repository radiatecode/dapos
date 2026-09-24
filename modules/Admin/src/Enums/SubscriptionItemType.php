<?php

namespace DA\Admin\Enums;

enum SubscriptionItemType: string
{
    case Plan = 'PLAN';
    case Addon = 'ADDON';

    public function label(): string
    {
        return match ($this) {
            self::Plan => 'Plan',
            self::Addon => 'Add-on',
        };
    }
}
