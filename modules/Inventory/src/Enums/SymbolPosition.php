<?php

namespace DA\Inventory\Enums;

enum SymbolPosition: string
{
    case LEFT = 'left';
    case RIGHT = 'right';

    public function label(): string
    {
        return match ($this) {
            self::LEFT => 'Left of the amount',
            self::RIGHT => 'Right of the amount',
        };
    }
}
