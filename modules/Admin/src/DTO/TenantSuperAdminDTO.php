<?php

namespace DA\Admin\DTO;

use Spatie\LaravelData\Data;

class TenantSuperAdminDTO extends Data
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
    ) {}
}
