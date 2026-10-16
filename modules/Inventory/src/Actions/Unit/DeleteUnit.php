<?php

namespace DA\Inventory\Actions\Unit;

use DA\Inventory\Models\Unit;
use Illuminate\Validation\ValidationException;

class DeleteUnit
{
    public function handle(array $ids): void
    {
        foreach ($ids as $id) {
            $unit = Unit::findOrFail($id);

            if (! $unit->authorizeAction($unit, 'delete')) {
                throw ValidationException::withMessages([
                    'unit' => 'You are not authorized to delete this unit.',
                ]);
            }

            $unit->delete();
        }
    }
}
