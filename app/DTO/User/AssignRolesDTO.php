<?php

namespace App\DTO\User;

use Spatie\LaravelData\Data;

class AssignRolesDTO extends Data
{
    /**
     * @param  list<int>  $role_ids
     */
    public function __construct(
        public array $role_ids,
    ) {}
}
