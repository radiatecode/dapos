<?php

namespace DA\Inventory\Actions\Unit;

use DA\Inventory\DTO\Unit\UnitDTO;
use DA\Inventory\Models\Unit;

class UpdateUnit
{
    public function handle(Unit $unit, UnitDTO $dto): Unit
    {
        $unit->name = $dto->name;
        $unit->short_name = $dto->shortName;
        $unit->code = $dto->code;
        $unit->unit_type = $dto->unitType;
        $unit->precision = $dto->precision;
        $unit->is_active = $dto->isActive;
        $unit->save();

        return $unit->refresh();
    }
}
