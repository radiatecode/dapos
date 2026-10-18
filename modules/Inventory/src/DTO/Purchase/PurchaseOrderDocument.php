<?php

namespace DA\Inventory\DTO\Purchase;

use DA\Inventory\Models\Purchase;
use DA\Inventory\Services\TenantNumberFormatter;

final class PurchaseOrderDocument
{
    public function __construct(
        public Purchase $purchase,
        public string $businessName,
        public string $generatedAt,
        public TenantNumberFormatter $numbers,
    ) {}

    public function fileName(): string
    {
        $number = preg_replace('/[^A-Za-z0-9._-]+/', '-', (string) $this->purchase->po_number) ?: 'purchase';

        return 'purchase-order-'.trim($number, '-').'.pdf';
    }
}
