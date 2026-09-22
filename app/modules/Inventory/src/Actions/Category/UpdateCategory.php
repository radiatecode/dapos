<?php

namespace DA\Inventory\Actions\Category;

use DA\Inventory\DTO\Category\CategoryDTO;
use DA\Inventory\Models\Category;
use Illuminate\Support\Facades\Storage;

class UpdateCategory
{
    public function handle(Category $category, CategoryDTO $dto): Category
    {
        $category->name = $dto->name;
        $category->slug = $dto->slug ?: Category::queries()->uniqueSlugFrom($dto->name, 'category', $category->id);
        $category->code = $dto->code;
        $category->description = $dto->description;
        $category->parent_id = $dto->parentId;
        $category->sort_order = $dto->sortOrder;
        $category->is_active = $dto->isActive;

        if ($dto->image !== null) {
            if (is_string($category->image) && $category->image !== '') {
                Storage::disk('public')->delete($category->image);
            }

            $category->image = $dto->image->store('catalog/categories', 'public');
        }

        $category->save();

        return $category->refresh();
    }
}
