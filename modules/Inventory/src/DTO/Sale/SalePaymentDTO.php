<?php

namespace DA\Inventory\DTO\Sale;

use Spatie\LaravelData\Data;

class SalePaymentDTO extends Data
{
    public function __construct(
        public int $paymentMethodId,
        public float $receivedAmount,
        public ?string $referenceNumber,
    ) {}
}
