<?php

namespace DA\Inventory\Actions\Category;

use DA\Inventory\DTO\Category\CategoryDTO;
use DA\Inventory\Models\Category;

class CreateCategory
{
    public function handle(CategoryDTO $dto): Category
    {
        $category = new Category;
        
        $category->name = $dto->name;
        $category->slug = $dto->slug ?: Category::queries()->uniqueSlugFrom($dto->name, 'category');
        $category->code = $dto->code;
        $category->description = $dto->description;
        $category->parent_id = $dto->parentId;
        $category->sort_order = $dto->sortOrder;
        $category->is_active = $dto->isActive;

        if ($dto->image !== null) {
            $category->image = $dto->image->store('catalog/categories', 'public');
        }

        $category->save();

        return $category;
    }
}
