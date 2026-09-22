<?php

namespace DA\Admin\Enums;

enum FeatureEnforcement: string
{
    case Resource = 'RESOURCE';
    case Consumption = 'CONSUMPTION';

    public function label(): string
    {
        return match ($this) {
            self::Resource => 'Resource',
            self::Consumption => 'Consumption',
        };
    }
}
