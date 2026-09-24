<?php

namespace DA\Inventory\DTO\Product;

use Spatie\LaravelData\Data;

class ProductAttributeDTO extends Data
{
    public function __construct(
        public int $attributeId,
        public int $sortOrder,
        public bool $isRequired,
    ) {}
}
