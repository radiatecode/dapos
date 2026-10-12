<?php

namespace DA\Inventory\Actions\Report;

use DA\Admin\Models\Tenant;
use DA\Inventory\DTO\Report\TopSalesReportExportDTO;
use DA\Inventory\DTO\Report\TopSalesReportFilterDTO;
use DA\Inventory\Models\Brand;
use DA\Inventory\Models\CurrencySetting;
use DA\Inventory\Models\SaleItem;
use DA\Inventory\Models\Store;
use DA\Inventory\Services\TenantNumberFormatter;

class PrepareTopSalesReportExport
{
    public function handle(TopSalesReportFilterDTO $filters): TopSalesReportExportDTO
    {
        /** @var Tenant $tenant */
        $tenant = Tenant::queries()->findById((int) auth()->user()->tenant_id);

        return new TopSalesReportExportDTO(
            businessName: (string) $tenant->name,
            generatedAt: now()->format('d M Y, h:i A'),
            numbers: TenantNumberFormatter::fromCurrencySetting(
                CurrencySetting::queries()->findForTenant(),
                (string) $tenant->currency,
            ),
            filterLabels: $this->filterLabels($filters),
            rows: SaleItem::queries()->topSalesRows($filters),
            totals: SaleItem::queries()->topSalesTotals($filters),
        );
    }

    /**
     * @return array<string, string>
     */
    private function filterLabels(TopSalesReportFilterDTO $filters): array
    {
        $labels = [
            'Period' => $this->periodLabel($filters),
            'Store' => $this->nameOrAll(Store::queries()->nameForAuthenticatedTenant(...), $filters->storeId, 'All stores'),
            'Brand' => $this->nameOrAll(Brand::queries()->nameForAuthenticatedTenant(...), $filters->brandId, 'All brands'),
        ];

        if ($filters->search !== null && $filters->search !== '') {
            $labels['Search'] = $filters->search;
        }

        return $labels;
    }

    private function periodLabel(TopSalesReportFilterDTO $filters): string
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
