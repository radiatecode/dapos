<?php

namespace DA\Inventory\Enums;

enum DecimalSeparator: string
{
    case DOT = 'dot';
    case COMMA = 'comma';

    public function label(): string
    {
        return match ($this) {
            self::DOT => 'Dot',
            self::COMMA => 'Comma',
        };
    }

    public function character(): string
    {
        return match ($this) {
            self::DOT => '.',
            self::COMMA => ',',
        };
    }
}
