<?php

namespace DA\Admin\Enums;

use InvalidArgumentException;

enum DefaultDataType: string
{
    case DEFAULT_ROLES = 'default_roles';
    case DEFAULT_UNIT = 'default_unit';

    public function label(): string
    {
        return match ($this) {
            self::DEFAULT_ROLES => 'Default Roles',
            self::DEFAULT_UNIT => 'Default Unit',
            default => throw new InvalidArgumentException('Invalid default data type'),
        };
    }
}
