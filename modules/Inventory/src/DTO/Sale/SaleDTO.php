<?php

namespace DA\Inventory\DTO\Sale;

use Spatie\LaravelData\Data;

class SaleDTO extends Data
{
    /**
     * @param  list<SaleItemDTO>  $items
     * @param  list<SalePaymentDTO>  $payments
     */
    public function __construct(
        public int $storeId,
        public ?int $customerId,
        public string $saleDate,
        public float $discountAmount,
        public string $taxAmount,
        public array $items,
        public array $payments,
        public ?int $discountType,
    ) {}
}
