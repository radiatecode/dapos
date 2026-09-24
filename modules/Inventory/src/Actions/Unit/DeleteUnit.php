<?php

namespace DA\Inventory\Actions\Unit;

use DA\Inventory\Models\Unit;

class DeleteUnit
{
    public function handle(array $ids): void
    {
        foreach ($ids as $id) {
            $unit = Unit::findOrFail($id);

            $unit->delete();
        }
    }
}
