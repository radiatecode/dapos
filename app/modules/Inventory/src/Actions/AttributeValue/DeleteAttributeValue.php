<?php

namespace DA\Inventory\Actions\AttributeValue;

use DA\Inventory\Models\AttributeValue;

class DeleteAttributeValue
{
    public function handle(AttributeValue $attributeValue): void
    {
        $attributeValue->delete();
    }
}
