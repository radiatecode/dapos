<?php

namespace DA\Inventory\Actions\Brand;

use DA\Inventory\Models\Brand;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class DeleteBrand
{
    public function handle(array $ids): void
    {
        foreach ($ids as $id) {
            $brand = Brand::findOrFail($id);

            if (! $brand->authorizeAction($brand, 'delete')) {
                throw ValidationException::withMessages([
                    'brand' => 'You are not authorized to delete this brand.',
                ]);
            }

            if (is_string($brand->logo) && $brand->logo !== '') {
                Storage::disk('public')->delete($brand->logo);
            }

            $brand->delete();
        }
    }
}
