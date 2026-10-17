<?php

namespace DA\Inventory\DTO\QuickSale;

use DA\Inventory\Enums\PaymentMethod;
use Spatie\LaravelData\Data;

class QuickSaleDTO extends Data
{
    /**
     * @param  list<QuickSaleItemDTO>  $items
     */
    public function __construct(
        public int $storeId,
        public string $saleDate,
        public PaymentMethod $paymentMethod,
        public string $receivedAmount,
        public ?string $referenceNumber,
        public ?string $note,
        public array $items,
    ) {}
}
