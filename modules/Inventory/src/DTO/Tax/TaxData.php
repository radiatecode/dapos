<?php

namespace DA\Inventory\DTO\Tax;

class TaxData
{
    public function __construct(
        public readonly string $tax_name,
        public readonly int $tax_type,
        public readonly float $tax_rate,
        public readonly bool $is_active = true,
        public readonly ?int $id = null,
    ) {}
}
