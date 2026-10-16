<?php

namespace App\Enums;

enum DefaultRole: string
{
    case Admin = 'admin';
    case Manager = 'manager';
    case Staff = 'staff';
    case Cashier = 'cashier';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::Manager => 'Manager',
            self::Staff => 'Staff',
            self::Cashier => 'Cashier',
        };
    }

    public function permissions(): array
    {
        return match ($this) {
            self::Admin => Permission::all(),
            self::Manager => Permission::manager(),
            self::Staff => Permission::staff(),
            self::Cashier => Permission::cashier(),
        };
    }
}
