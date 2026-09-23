<?php

namespace DA\Inventory\Actions\Attribute;

use DA\Inventory\Models\Attribute;

class DeleteAttribute
{
    public function handle(int $id): void
    {
        $attribute = Attribute::findOrFail($id);

        $attribute->delete();
    }
}
