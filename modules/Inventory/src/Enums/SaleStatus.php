<?php

namespace DA\Inventory\Enums;

enum SaleStatus: string
{
    case Completed = 'Completed';

    public function label(): string
    {
        return $this->value;
    }
}
