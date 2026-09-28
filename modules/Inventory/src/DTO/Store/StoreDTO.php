<?php

namespace DA\Inventory\DTO\Store;

use Spatie\LaravelData\Data;

class StoreDTO extends Data
{
    public function __construct(
        public string $name,
        public string $address,
        public bool $isActive,
    ) {}
}
