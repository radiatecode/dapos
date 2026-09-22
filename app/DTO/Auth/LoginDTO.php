<?php

namespace App\DTO\Auth;

use Spatie\LaravelData\Data;

class LoginDTO extends Data
{
    public function __construct(
        public string $email,
        public string $password,
        public string $device_name = 'pos',
    ) {}
}
