<?php

namespace DA\Admin\DTO;

use DA\Admin\Enums\PaymentMethod;
use Spatie\LaravelData\Data;

class RecordPaymentDTO extends Data
{
    public function __construct(
        public PaymentMethod $payment_method = PaymentMethod::Manual,
        public ?string $transaction_id = null,
    ) {}
}
