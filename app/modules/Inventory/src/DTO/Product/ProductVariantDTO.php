<?php

namespace DA\Inventory\DTO\Product;

use DA\Inventory\Enums\BarcodeType;
use Illuminate\Http\UploadedFile;
use Spatie\LaravelData\Data;

class ProductVariantDTO extends Data
{
    /**
     * @param  list<VariantAttributeValueDTO>  $attributeValues
     */
    public function __construct(
        public ?int $id,
        public string $sku,
        public string $barcode,
        public BarcodeType $barcodeType,
        public ?string $name,
        public ?string $costPrice,
        public ?string $sellingPrice,
        public ?string $compareAtPrice,
        public ?string $weight,
        public ?string $quantity,
        public ?int $minStockLevel,
        public ?UploadedFile $image,
        public bool $isDefault,
        public bool $isActive,
        public array $attributeValues,
    ) {}
}
