<?php

namespace DA\Inventory\DTO\PurchaseReturn;

use DA\Inventory\Enums\PurchaseReturnStatus;
use Spatie\LaravelData\Data;

class PurchaseReturnDTO extends Data
{
    /**
     * @param  list<PurchaseReturnItemDTO>  $items
     */
    public function __construct(
        public int $purchaseId,
        public string $returnDate,
        public string $discountAmount,
        public string $taxAmount,
        public PurchaseReturnStatus $status,
        public ?string $reason,
        public ?string $notes,
        public array $items,
    ) {}
}
