<?php

namespace App\DTO\User;

use Spatie\LaravelData\Data;

class UpdatePasswordDTO extends Data
{
    public function __construct(
        public string $password,
    ) {}
}
