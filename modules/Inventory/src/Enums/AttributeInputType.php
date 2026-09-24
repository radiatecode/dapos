<?php

namespace DA\Inventory\Enums;

enum AttributeInputType: string
{
    case Select = 'select';
    case Multiselect = 'multiselect';
    case Text = 'text';
    case Number = 'number';

    public function label(): string
    {
        return match ($this) {
            self::Select => 'Select',
            self::Multiselect => 'Multiselect',
            self::Text => 'Text',
            self::Number => 'Number',
        };
    }
}
