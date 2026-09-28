<?php

namespace DA\Inventory\DTO\Supplier;

use Spatie\LaravelData\Data;

class SupplierDTO extends Data
{
    public function __construct(
        public string $name,
        public string $email,
        public string $phone,
        public string $address,
        public ?string $city,
        public ?string $state,
        public ?string $country,
        public ?string $postalCode,
        public bool $isActive,
    ) {}
}
