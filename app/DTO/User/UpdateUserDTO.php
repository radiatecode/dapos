<?php

namespace App\DTO\User;

use Spatie\LaravelData\Data;

class UpdateUserDTO extends Data
{
    public function __construct(
        public string $name,
        public string $email,
    ) {}
}
