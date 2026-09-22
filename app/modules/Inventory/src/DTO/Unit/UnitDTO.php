<?php

namespace DA\Inventory\DTO\Unit;

use DA\Inventory\Enums\UnitType;
use Spatie\LaravelData\Data;

class UnitDTO extends Data
{
    public function __construct(
        public string $name,
        public string $shortName,
        public string $code,
        public UnitType $unitType,
        public int $precision,
        public bool $isActive,
    ) {}
}
