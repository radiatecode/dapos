<?php

namespace DA\Admin\Enums;

enum TenantStatus: int
{
    case Active = 1;
    case Suspended = 0;

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Suspended => 'Suspended',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::Suspended => 'danger',
        };
    }
}
