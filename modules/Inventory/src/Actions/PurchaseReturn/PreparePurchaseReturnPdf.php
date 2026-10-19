<?php

namespace DA\Inventory\Actions\PurchaseReturn;

use DA\Admin\Models\Tenant;
use DA\Inventory\DTO\PurchaseReturn\PurchaseReturnDocument;
use DA\Inventory\Models\CurrencySetting;
use DA\Inventory\Models\PurchaseReturn;
use DA\Inventory\Services\TenantNumberFormatter;

class PreparePurchaseReturnPdf
{
    public function handle(int $id): PurchaseReturnDocument
    {
        /** @var Tenant $tenant */
        $tenant = Tenant::queries()->findById((int) auth()->user()->tenant_id);

        return new PurchaseReturnDocument(
            purchaseReturn: PurchaseReturn::queries()->findForDetail($id),
            businessName: (string) $tenant->name,
            generatedAt: now()->format('d M Y, h:i A'),
            numbers: TenantNumberFormatter::fromCurrencySetting(
                CurrencySetting::queries()->findForTenant(),
                (string) $tenant->currency,
            ),
        );
    }
}
