<?php

namespace DA\Admin\Enums;

enum FeatureType: string
{
    case Boolean = 'BOOLEAN';
    case Limit = 'LIMIT';

    public function label(): string
    {
        return match ($this) {
            self::Boolean => 'Boolean',
            self::Limit => 'Limit',
        };
    }
}
