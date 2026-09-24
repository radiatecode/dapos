<?php

namespace DA\Inventory\Actions\Category;

use DA\Inventory\Models\Category;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class DeleteCategory
{
    public function handle(array $ids): void
    {
        foreach ($ids as $id) {
            $category = Category::findOrFail($id);

            if (Category::queries()->hasChildren($category->id)) {
                throw ValidationException::withMessages([
                    'category' => 'This category has child categories and cannot be deleted.',
                ]);
            }

            if (is_string($category->image) && $category->image !== '') {
                Storage::disk('public')->delete($category->image);
            }

            $category->delete();
        }
    }
}
