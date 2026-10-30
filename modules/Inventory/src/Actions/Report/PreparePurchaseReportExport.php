<?php

namespace DA\Inventory\Actions\Report;

use DA\Admin\Models\Tenant;
use DA\Inventory\DTO\Report\PurchaseReportExportDTO;
use DA\Inventory\DTO\Report\PurchaseReportFilterDTO;
use DA\Inventory\Models\CurrencySetting;
use DA\Inventory\Models\Purchase;
use DA\Inventory\Models\Store;
use DA\Inventory\Models\Supplier;
use DA\Inventory\Services\TenantNumberFormatter;

class PreparePurchaseReportExport
{
    public function handle(PurchaseReportFilterDTO $filters): PurchaseReportExportDTO
    {
        /** @var Tenant $tenant */
        $tenant = Tenant::queries()->findById((int) auth()->user()->tenant_id);

        return new PurchaseReportExportDTO(
            businessName: (string) $tenant->name,
            generatedAt: now()->format('d M Y, h:i A'),
            numbers: TenantNumberFormatter::fromCurrencySetting(
                CurrencySetting::queries()->findForTenant(),
                (string) $tenant->currency,
            ),
            filterLabels: $this->filterLabels($filters),
            rows: Purchase::queries()->reportRows($filters),
            totals: Purchase::queries()->reportTotals($filters),
        );
    }

    /**
     * Totals ignore the payment preset so the summary stays in place while the
     * purchase list follows the selected payment status.
     *
     * @return array<string, string>
     */
    private function filterLabels(PurchaseReportFilterDTO $filters): array
    {
        $labels = [
            'Period' => $this->periodLabel($filters),
            'Store' => $this->nameOrAll(Store::queries()->nameForAuthenticatedTenant(...), $filters->storeId, 'All stores'),
            'Supplier' => $this->nameOrAll(Supplier::queries()->nameForAuthenticatedTenant(...), $filters->supplierId, 'All suppliers'),
            'Payment' => $filters->paymentStatus?->value ?? 'All payments',
        ];

        if ($filters->search !== null && $filters->search !== '') {
            $labels['Search'] = $filters->search;
        }

        return $labels;
    }

    private function periodLabel(PurchaseReportFilterDTO $filters): string
    {
        return match (true) {
            $filters->dateFrom !== null && $filters->dateTo !== null => $filters->dateFrom.' to '.$filters->dateTo,
            $filters->dateFrom !== null => 'From '.$filters->dateFrom,
            $filters->dateTo !== null => 'Up to '.$filters->dateTo,
            default => 'All time',
        };
    }

    /**
     * @param  callable(int): (string|null)  $resolveName
     */
    private function nameOrAll(callable $resolveName, ?int $id, string $allLabel): string
    {
        if ($id === null) {
            return $allLabel;
        }

        return $resolveName($id) ?? 'Not found';
    }
}
