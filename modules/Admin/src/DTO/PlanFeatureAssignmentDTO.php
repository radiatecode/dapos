<?php

namespace DA\Admin\DTO;

use Spatie\LaravelData\Data;

class PlanFeatureAssignmentDTO extends Data
{
    public function __construct(
        public int $feature_id,
        public ?string $value,
        public bool $is_unlimited = false,
    ) {}
}
