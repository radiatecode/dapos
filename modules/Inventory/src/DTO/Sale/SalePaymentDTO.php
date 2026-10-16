<?php

namespace DA\Inventory\DTO\Sale;

use Spatie\LaravelData\Data;

class SalePaymentDTO extends Data
{
    public function __construct(
        public int $paymentMethod,
        public float $receivedAmount,
        public float $orderAmount,
        public float $dueAmount,
        public float $returnedAmount,
        public ?string $referenceNumber,
    ) {}
}
