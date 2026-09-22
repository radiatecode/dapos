<?php

namespace DA\Inventory\Actions\Unit;

use DA\Inventory\Models\Unit;

class DeleteUnit
{
    public function handle(Unit $unit): void
    {
        $unit->delete();
    }
}
