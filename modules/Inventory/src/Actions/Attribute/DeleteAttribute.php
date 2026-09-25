<?php

namespace DA\Inventory\Actions\Attribute;

use DA\Inventory\Models\Attribute;

class DeleteAttribute
{
    public function handle(array $ids): void
    {
        foreach ($ids as $id) {
            $attribute = Attribute::findOrFail($id);

            $attribute->delete();
        }
    }
}
