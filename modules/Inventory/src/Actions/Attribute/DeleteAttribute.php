<?php

namespace DA\Inventory\Actions\Attribute;

use DA\Inventory\Models\Attribute;
use Illuminate\Validation\ValidationException;

class DeleteAttribute
{
    public function handle(array $ids): void
    {
        foreach ($ids as $id) {
            $attribute = Attribute::findOrFail($id);

            if (! $attribute->authorizeAction($attribute, 'delete')) {
                throw ValidationException::withMessages([
                    'attribute' => 'You are not authorized to delete this attribute.',
                ]);
            }

            $attribute->delete();
        }
    }
}
