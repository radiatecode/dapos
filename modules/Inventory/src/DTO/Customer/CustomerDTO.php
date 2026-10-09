<?php

namespace DA\Inventory\DTO\Customer;

use Spatie\LaravelData\Data;

class CustomerDTO extends Data
{
    public function __construct(
        public string $name,
        public string $phone,
        public string $email,
        public ?string $address,
    ) {}
}
