<?php

namespace DA\Inventory\DTO\Report;

use DA\Inventory\Enums\PurchasePaymentStatus;
use Spatie\LaravelData\Data;

class PurchaseReportFilterDTO extends Data
{
    public function __construct(
        public ?string $dateFrom,
        public ?string $dateTo,
        public ?int $storeId,
        public ?int $supplierId,
        public ?PurchasePaymentStatus $paymentStatus,
        public ?string $search,
    ) {}
}
