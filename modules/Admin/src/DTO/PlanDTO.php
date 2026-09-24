<?php

namespace DA\Admin\DTO;

use DA\Admin\Enums\BillingInterval;
use Spatie\LaravelData\Data;

class PlanDTO extends Data
{
    /**
     * @param  list<PlanFeatureAssignmentDTO>  $features
     */
    public function __construct(
        public string $name,
        public string $code,
        public ?string $description,
        public BillingInterval $billing_interval,
        public string $price,
        public int $currency_id,
        public int $trial_days,
        public bool $is_active,
        public array $features = [],
    ) {}
}
