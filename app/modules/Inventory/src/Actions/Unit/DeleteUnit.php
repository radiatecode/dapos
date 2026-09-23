<?php

namespace DA\Inventory\Actions\Unit;

use DA\Inventory\Models\Unit;

class DeleteUnit
{
    public function handle(int $id): void
    {
        $unit = Unit::findOrFail($id);

        $unit->delete();
    }
}
