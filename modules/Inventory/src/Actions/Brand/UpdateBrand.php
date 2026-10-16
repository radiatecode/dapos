<?php

namespace DA\Inventory\Actions\Brand;

use DA\Inventory\DTO\Brand\BrandDTO;
use DA\Inventory\Models\Brand;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class UpdateBrand
{
    public function handle(int $brandId, BrandDTO $dto): Brand
    {
        $brand = Brand::query()->findOrFail($brandId);

        if (! $brand->authorizeAction($brand, 'update')) {
            throw ValidationException::withMessages([
                'brand' => 'You are not authorized to update this brand.',
            ]);
        }

        $brand->name = $dto->name;
        $brand->slug = $dto->slug ?: Brand::queries()->uniqueSlugFrom($dto->name, 'brand', $brand->id);
        $brand->code = $dto->code;
        $brand->description = $dto->description;
        $brand->is_active = $dto->isActive;

        if ($dto->logo !== null) {
            if (is_string($brand->logo) && $brand->logo !== '') {
                Storage::disk('public')->delete($brand->logo);
            }

            $brand->logo = $dto->logo->store('catalog/brands', 'public');
        }

        $brand->save();

        return $brand->refresh();
    }
}
