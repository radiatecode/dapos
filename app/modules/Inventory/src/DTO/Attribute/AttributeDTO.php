<?php

namespace DA\Inventory\DTO\Attribute;

use DA\Inventory\Enums\AttributeInputType;
use Spatie\LaravelData\Data;

class AttributeDTO extends Data
{
    public function __construct(
        public string $name,
        public ?string $slug,
        public ?string $code,
        public AttributeInputType $inputType,
        public int $sortOrder,
        public bool $isActive,
    ) {}
}
