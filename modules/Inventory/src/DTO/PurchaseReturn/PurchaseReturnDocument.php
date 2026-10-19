<?php

namespace DA\Inventory\DTO\PurchaseReturn;

use DA\Inventory\Models\PurchaseReturn;
use DA\Inventory\Services\TenantNumberFormatter;

final class PurchaseReturnDocument
{
    public function __construct(
        public PurchaseReturn $purchaseReturn,
        public string $businessName,
        public string $generatedAt,
        public TenantNumberFormatter $numbers,
    ) {}

    public function fileName(): string
    {
        $number = preg_replace('/[^A-Za-z0-9._-]+/', '-', (string) $this->purchaseReturn->return_number) ?: 'return';

        return 'purchase-return-'.trim($number, '-').'.pdf';
    }
}
