<?php

namespace App\DTO\User;

use Spatie\LaravelData\Data;

class AssignRolesDTO extends Data
{
    /**
     * @param  list<int>  $role_ids
     */
    public function __construct(
        public int $role_id,
    ) {}
}
