<?php

namespace DA\Inventory\DTO\AttributeValue;

use Spatie\LaravelData\Data;

class AttributeValueDTO extends Data
{
    public function __construct(
        public int $attributeId,
        public string $value,
        public ?string $slug,
        public ?string $code,
        public int $sortOrder,
        public bool $isActive,
    ) {}
}
