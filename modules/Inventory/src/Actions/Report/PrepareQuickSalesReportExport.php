<?php

namespace DA\Inventory\Actions\Report;

use DA\Admin\Models\Tenant;
use DA\Inventory\DTO\QuickSale\QuickSaleFilterDTO;
use DA\Inventory\DTO\Report\QuickSalesReportExportDTO;
use DA\Inventory\Models\CurrencySetting;
use DA\Inventory\Models\QuickSale;
use DA\Inventory\Models\Store;
use DA\Inventory\Services\TenantNumberFormatter;

class PrepareQuickSalesReportExport
{
    public function handle(QuickSaleFilterDTO $filters): QuickSalesReportExportDTO
    {
        /** @var Tenant $tenant */
        $tenant = Tenant::queries()->findById((int) auth()->user()->tenant_id);

        return new QuickSalesReportExportDTO(
            businessName: (string) $tenant->name,
            generatedAt: now()->format('d M Y, h:i A'),
            numbers: TenantNumberFormatter::fromCurrencySetting(
                CurrencySetting::queries()->findForTenant(),
                (string) $tenant->currency,
            ),
            filterLabels: $this->filterLabels($filters),
            rows: QuickSale::queries()->dailyReportRows($filters),
            totals: QuickSale::queries()->dailyReportTotals($filters),
        );
    }

    /**
     * @return array<string, string>
     */
    private function filterLabels(QuickSaleFilterDTO $filters): array
    {
        $storeName = $filters->storeId === null
            ? 'All stores'
            : (Store::queries()->nameForAuthenticatedTenant($filters->storeId) ?? 'Not found');

        return [
            'Period' => $this->periodLabel($filters),
            'Store' => $storeName,
            'Payment' => $filters->paymentMethod?->label() ?? 'All payment methods',
        ];
    }

    private function periodLabel(QuickSaleFilterDTO $filters): string
    {
        return match (true) {
            $filters->dateFrom !== null && $filters->dateTo !== null => $filters->dateFrom.' to '.$filters->dateTo,
            $filters->dateFrom !== null => 'From '.$filters->dateFrom,
            $filters->dateTo !== null => 'Up to '.$filters->dateTo,
            default => 'All time',
        };
    }
}
