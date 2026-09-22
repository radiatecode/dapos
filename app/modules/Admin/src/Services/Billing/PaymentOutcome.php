<?php

namespace DA\Admin\Services\Billing;

use DA\Admin\Enums\PaymentStatus;
use Illuminate\Support\Carbon;

class PaymentOutcome
{
    public function __construct(
        public PaymentStatus $status,
        public string $transactionId,
        public ?Carbon $paidAt,
    ) {}
}
