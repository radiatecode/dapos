<?php

namespace DA\Inventory\DTO\Product;

use Spatie\LaravelData\Data;

class BarcodeLabelFilterDTO extends Data
{
    public function __construct(
        public ?int $categoryId,
        public ?string $search,
    ) {}
}
