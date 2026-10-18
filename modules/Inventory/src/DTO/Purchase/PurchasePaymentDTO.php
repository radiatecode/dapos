<?php

namespace DA\Inventory\DTO\Purchase;

use Spatie\LaravelData\Data;

class PurchasePaymentDTO extends Data
{
    public function __construct(
        public string $amount,
        public ?string $referenceNumber,
        public ?string $note,
    ) {}
}
