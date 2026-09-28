<?php

namespace DA\Inventory\DTO\Product;

use DA\Inventory\Enums\ProductType;
use Illuminate\Http\UploadedFile;
use Spatie\LaravelData\Data;

class ProductDTO extends Data
{
    /**
     * @param  list<ProductAttributeDTO>  $attributes
     * @param  list<ProductVariantDTO>  $variants
     */
    public function __construct(
        public int $categoryId,
        public int $brandId,
        public int $unitId,
        public string $name,
        public ?string $slug,
        public ?string $description,
        public ?UploadedFile $image,
        public ProductType $productType,
        public bool $trackInventory,
        public bool $isActive,
        public bool $isStockOut,
        public ?string $manufactureDate,
        public ?string $expireDate,
        public ?int $warrantyInDays,
        public ?int $guaranteeInDays,
        public array $attributes,
        public array $variants,
        public ?int $storeId,
    ) {}
}
