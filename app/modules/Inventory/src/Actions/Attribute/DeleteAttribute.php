<?php

namespace DA\Inventory\Actions\Attribute;

use DA\Inventory\Models\Attribute;

class DeleteAttribute
{
    public function handle(Attribute $attribute): void
    {
        $attribute->delete();
    }
}
