<?php

namespace DA\Inventory\Actions\Attribute;

use DA\Inventory\DTO\Attribute\AttributeDTO;
use DA\Inventory\Models\Attribute;
use Illuminate\Validation\ValidationException;

class UpdateAttribute
{
    public function handle(int $id, AttributeDTO $dto): Attribute
    {
        $attribute = Attribute::findOrFail($id);

        if (! $attribute->authorizeAction($attribute, 'update')) {
            throw ValidationException::withMessages([
                'attribute' => 'You are not authorized to update this attribute.',
            ]);
        }

        $attribute->name = $dto->name;
        $attribute->slug = $dto->slug ?: Attribute::queries()->uniqueSlugFrom($dto->name, 'attribute', $attribute->id);
        $attribute->code = $dto->code;
        $attribute->input_type = $dto->inputType;
        $attribute->sort_order = $dto->sortOrder;
        $attribute->is_active = $dto->isActive;
        $attribute->save();

        return $attribute->refresh();
    }
}
