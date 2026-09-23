<?php

namespace DA\Inventory\DTO\Product;

use Spatie\LaravelData\Data;

class VariantAttributeValueDTO extends Data
{
    public function __construct(
        public int $attributeId,
        public int $attributeValueId,
    ) {}
}
