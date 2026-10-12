<?php

namespace DA\Inventory\Enums;

enum ThousandsSeparator: string
{
    case COMMA = 'comma';
    case DOT = 'dot';
    case SPACE = 'space';
    case NONE = 'none';

    public function label(): string
    {
        return match ($this) {
            self::COMMA => 'Comma',
            self::DOT => 'Dot',
            self::SPACE => 'Space',
            self::NONE => 'None',
        };
    }

    public function character(): string
    {
        return match ($this) {
            self::COMMA => ',',
            self::DOT => '.',
            self::SPACE => ' ',
            self::NONE => '',
        };
    }
}
