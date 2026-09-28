<?php

namespace DA\Inventory\Enums;

enum InventoryLayerStatus: string
{
    case Open = 'Open';
    case Depleted = 'Depleted';

    public function label(): string
    {
        return $this->value;
    }
}
