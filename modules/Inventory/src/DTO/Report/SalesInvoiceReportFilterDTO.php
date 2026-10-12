<?php

namespace DA\Inventory\DTO\Report;

use DA\Inventory\Enums\SalePaymentStatus;
use Spatie\LaravelData\Data;

class SalesInvoiceReportFilterDTO extends Data
{
    public function __construct(
        public ?string $dateFrom,
        public ?string $dateTo,
        public ?int $storeId,
        public ?int $customerId,
        public ?SalePaymentStatus $paymentStatus,
        public ?string $search,
    ) {}
}
