<?php

namespace DA\Inventory\DTO\Product;

use Spatie\LaravelData\Data;

class ProductBatchSampleDTO extends Data
{
    public function __construct(
        public string $companyName,
        public string $address,
    ) {}
}
