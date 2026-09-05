<?php

namespace DA\Admin\DTO;

use DA\Admin\Enums\BillingInterval;
use Spatie\LaravelData\Data;

class AddonDTO extends Data
{
    public function __construct(
        public string $name,
        public string $code,
        public ?string $description,
        public BillingInterval $billing_interval,
        public string $price,
        public int $currency_id,
        public bool $is_active,
    ) {}
}
