<?php

namespace App\DTO\Role;

use Spatie\LaravelData\Data;

class RoleDTO extends Data
{
    /**
     * @param  list<string>  $permissions
     */
    public function __construct(
        public string $name,
        public ?string $slug,
        public array $permissions,
    ) {}
}
