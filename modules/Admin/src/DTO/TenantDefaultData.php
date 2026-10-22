<?php

namespace DA\Admin\DTO;

use Spatie\LaravelData\Data;

class TenantDefaultData extends Data
{
    public function __construct(
        public readonly ?string $default_roles,
        public readonly ?string $default_unit,
        public readonly bool $force_update = false,
    ) {}
}
