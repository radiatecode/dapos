<?php

namespace DA\Admin\DTO;

use DA\Admin\Enums\FeatureType;
use Spatie\LaravelData\Data;

class FeatureDTO extends Data
{
    public function __construct(
        public string $name,
        public string $code,
        public FeatureType $type,
        public ?string $description,
    ) {}
}
