<?php

namespace DA\Inventory\DTO\Tax;

class TaxSettingData
{
    public function __construct(
        public readonly int $tax_mode,
        public readonly int $tax_calculation_base,
    ) {}
}
