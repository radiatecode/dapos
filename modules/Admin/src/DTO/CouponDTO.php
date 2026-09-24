<?php

namespace DA\Admin\DTO;

use DA\Admin\Enums\CouponDiscountType;
use Illuminate\Support\Carbon;
use Spatie\LaravelData\Data;

class CouponDTO extends Data
{
    public function __construct(
        public string $code,
        public string $name,
        public ?string $description,
        public CouponDiscountType $discount_type,
        public string $discount_value,
        public ?int $currency_id,
        public ?int $max_redemptions,
        public ?int $max_redemptions_per_tenant,
        public ?string $minimum_amount,
        public ?Carbon $starts_at,
        public ?Carbon $ends_at,
        public bool $is_active,
    ) {}
}
