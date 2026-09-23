<?php

namespace DA\Inventory\Actions\AttributeValue;

use DA\Inventory\Models\AttributeValue;

class DeleteAttributeValue
{
    public function handle(int $id, int $valueId): void
    {
        $attributeValue = AttributeValue::query()
            ->where('attribute_id', $id)
            ->findOrFail($valueId);

        $attributeValue->delete();
    }
}
