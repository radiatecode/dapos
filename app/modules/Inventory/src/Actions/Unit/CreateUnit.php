<?php

namespace DA\Inventory\Actions\Unit;

use DA\Inventory\DTO\Unit\UnitDTO;
use DA\Inventory\Models\Unit;

class CreateUnit
{
    public function handle(UnitDTO $dto): Unit
    {
        $unit = new Unit;

        $unit->name = $dto->name;
        $unit->code = $dto->code;
        $unit->unit_type = $dto->unitType;
        $unit->precision = $dto->precision;
        $unit->is_active = $dto->isActive;
        $unit->save();

        return $unit;
    }
}
