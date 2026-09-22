<?php

namespace DA\Inventory\Actions\Brand;

use DA\Inventory\Models\Brand;
use Illuminate\Support\Facades\Storage;

class DeleteBrand
{
    public function handle(int $brandId): void
    {
        $brand = Brand::findOrFail($brandId);

        if (is_string($brand->logo) && $brand->logo !== '') {
            Storage::disk('public')->delete($brand->logo);
        }

        $brand->delete();
    }
}
