<?php

namespace DA\Admin\DTO;

use Spatie\LaravelData\Data;

class CreateSubscriptionDTO extends Data
{
    /**
     * @param  list<int>  $addon_ids
     */
    public function __construct(
        public int $tenant_id,
        public int $plan_id,
        public bool $start_trial,
        public array $addon_ids = [],
        public int $grace_days = 7,
    ) {}
}
