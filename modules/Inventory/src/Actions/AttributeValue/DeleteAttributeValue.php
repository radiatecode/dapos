<?php

namespace DA\Inventory\Actions\AttributeValue;

use DA\Inventory\Models\AttributeValue;

class DeleteAttributeValue
{
    public function handle(array $ids): void
    {
        foreach ($ids as $id) {
            $attributeValue = AttributeValue::query()->findOrFail($id);

            $attributeValue->delete();
        }
    }
}
