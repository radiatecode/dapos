<?php

namespace DA\Inventory\Actions\Unit;

use DA\Inventory\DTO\Unit\UnitDTO;
use DA\Inventory\Models\Unit;
use Illuminate\Validation\ValidationException;

class UpdateUnit
{
    public function handle(int $id, UnitDTO $dto): Unit
    {
        $unit = Unit::findOrFail($id);

        if (! $unit->authorizeAction($unit, 'update')) {
            throw ValidationException::withMessages([
                'unit' => 'You are not authorized to update this unit.',
            ]);
        }

        $unit->name = $dto->name;
        $unit->code = $dto->code;
        $unit->unit_type = $dto->unitType;
        $unit->precision = $dto->precision;
        $unit->is_active = $dto->isActive;

        $unit->save();

        return $unit->refresh();
    }
}
