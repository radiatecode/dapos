<?php

namespace DA\Inventory\DTO\Purchase;

use DA\Inventory\Enums\PurchasePaymentStatus;
use DA\Inventory\Enums\PurchaseStatus;
use Spatie\LaravelData\Data;

class PurchaseDTO extends Data
{
    /**
     * @param  list<PurchaseOrderItemDTO>  $items
     */
    public function __construct(
        public int $supplierId,
        public int $storeId,
        public ?string $poNumber,
        public string $orderDate,
        public string $currency,
        public string $discountAmount,
        public string $taxAmount,
        public string $shippingAmount,
        public PurchaseStatus $status,
        public PurchasePaymentStatus $paymentStatus,
        public ?string $notes,
        public array $items,
    ) {}
}
