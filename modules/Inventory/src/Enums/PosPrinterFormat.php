<?php

namespace DA\Inventory\Enums;

enum PosPrinterFormat: string
{
    case THERMAL_80MM = 'thermal_80mm';
    case THERMAL_58MM = 'thermal_58mm';
    case THERMAL_56MM = 'thermal_56mm';

    public function label(): string
    {
        return match ($this) {
            self::THERMAL_80MM => 'Thermal 80mm',
            self::THERMAL_58MM => 'Thermal 58mm',
            self::THERMAL_56MM => 'Thermal 56mm',
        };
    }
}
