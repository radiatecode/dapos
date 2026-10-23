<?php

namespace App\DTO\User;

use Spatie\LaravelData\Data;

class CreateUserDTO extends Data
{
    /**
     * @param  list<int>  $storeIds
     */
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
        public ?int $role_id = null,
        public array $storeIds = [],
    ) {}
}
