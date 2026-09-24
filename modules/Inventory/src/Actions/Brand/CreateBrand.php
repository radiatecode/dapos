<?php

namespace DA\Inventory\Actions\Brand;

use DA\Inventory\DTO\Brand\BrandDTO;
use DA\Inventory\Models\Brand;

class CreateBrand
{
    public function handle(BrandDTO $dto): Brand
    {
        $brand = new Brand;
        $brand->name = $dto->name;
        $brand->slug = $dto->slug ?: Brand::queries()->uniqueSlugFrom($dto->name, 'brand');
        $brand->code = $dto->code;
        $brand->description = $dto->description;
        $brand->is_active = $dto->isActive;

        if ($dto->logo !== null) {
            $brand->logo = $dto->logo->store('catalog/brands', 'public');
        }

        $brand->save();

        return $brand;
    }
}
