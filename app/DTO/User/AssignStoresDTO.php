<?php

namespace App\DTO\User;

use Spatie\LaravelData\Data;

class AssignStoresDTO extends Data
{
    /**
     * @param  list<int>  $storeIds
     */
    public function __construct(
        public array $storeIds,
    ) {}
}
