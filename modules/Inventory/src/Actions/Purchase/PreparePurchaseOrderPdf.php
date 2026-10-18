<?php

namespace DA\Inventory\Actions\Purchase;

use DA\Admin\Models\Tenant;
use DA\Inventory\DTO\Purchase\PurchaseOrderDocument;
use DA\Inventory\Models\CurrencySetting;
use DA\Inventory\Models\Purchase;
use DA\Inventory\Services\TenantNumberFormatter;

class PreparePurchaseOrderPdf
{
    public function handle(int $id): PurchaseOrderDocument
    {
        /** @var Tenant $tenant */
        $tenant = Tenant::queries()->findById((int) auth()->user()->tenant_id);

        return new PurchaseOrderDocument(
            purchase: Purchase::queries()->findForDetail($id),
            businessName: (string) $tenant->name,
            generatedAt: now()->format('d M Y, h:i A'),
            numbers: TenantNumberFormatter::fromCurrencySetting(
                CurrencySetting::queries()->findForTenant(),
                (string) $tenant->currency,
            ),
        );
    }
}
