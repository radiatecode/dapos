<?php

namespace App\DTO\User;

use Spatie\LaravelData\Data;

class CreateUserDTO extends Data
{
    /**
     * @param  list<int>  $role_ids
     */
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
        public array $role_ids = [],
    ) {}
}
