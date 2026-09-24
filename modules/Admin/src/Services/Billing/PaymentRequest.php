<?php

namespace DA\Admin\Services\Billing;

use DA\Admin\Enums\PaymentMethod;
use DA\Admin\Enums\PaymentStatus;
use DA\Admin\Models\Invoice;

class PaymentRequest
{
    public function __construct(
        public Invoice $invoice,
        public PaymentStatus $intendedStatus,
        public PaymentMethod $method,
        public string $amount,
        public ?string $transactionId = null,
    ) {}
}
