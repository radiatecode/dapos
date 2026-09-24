<?php

namespace DA\Admin\DTO;

use Spatie\LaravelData\Data;

class GenerateInvoiceDTO extends Data
{
    public function __construct(
        public int $subscription_id,
        public ?string $coupon_code = null,
    ) {}
}
