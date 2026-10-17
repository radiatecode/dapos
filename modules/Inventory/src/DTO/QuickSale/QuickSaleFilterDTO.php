<?php

namespace DA\Inventory\DTO\QuickSale;

use DA\Inventory\Enums\PaymentMethod;
use Spatie\LaravelData\Data;

class QuickSaleFilterDTO extends Data
{
    public function __construct(
        public ?string $dateFrom,
        public ?string $dateTo,
        public ?int $storeId,
        public ?PaymentMethod $paymentMethod,
        public ?string $search,
    ) {}
}
