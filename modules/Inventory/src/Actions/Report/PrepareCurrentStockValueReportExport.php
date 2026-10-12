<?php

namespace DA\Inventory\Actions\Report;

use DA\Admin\Models\Tenant;
use DA\Inventory\DTO\Report\CurrentStockValueReportExportDTO;
use DA\Inventory\DTO\Report\CurrentStockValueReportFilterDTO;
use DA\Inventory\Models\Brand;
use DA\Inventory\Models\Category;
use DA\Inventory\Models\CurrencySetting;
use DA\Inventory\Models\InventoryBalance;
use DA\Inventory\Models\Store;
use DA\Inventory\Services\TenantNumberFormatter;

class PrepareCurrentStockValueReportExport
{
    public function handle(CurrentStockValueReportFilterDTO $filters): CurrentStockValueReportExportDTO
    {
        /** @var Tenant $tenant */
        $tenant = Tenant::queries()->findById((int) auth()->user()->tenant_id);

        return new CurrentStockValueReportExportDTO(
            businessName: (string) $tenant->name,
            generatedAt: now()->format('d M Y, h:i A'),
            numbers: TenantNumberFormatter::fromCurrencySetting(
                CurrencySetting::queries()->findForTenant(),
                (string) $tenant->currency,
            ),
            filterLabels: $this->filterLabels($filters),
            rows: InventoryBalance::queries()->currentStockValueReportRows($filters),
            totals: InventoryBalance::queries()->currentStockValueReportTotals($filters),
        );
    }

    /**
     * @return array<string, string>
     */
    private function filterLabels(CurrentStockValueReportFilterDTO $filters): array
    {
        $labels = [
            'Store' => $this->nameOrAll(Store::queries()->nameForAuthenticatedTenant(...), $filters->storeId, 'All stores'),
            'Category' => $this->nameOrAll(Category::queries()->nameForAuthenticatedTenant(...), $filters->categoryId, 'All categories'),
            'Brand' => $this->nameOrAll(Brand::queries()->nameForAuthenticatedTenant(...), $filters->brandId, 'All brands'),
            'Availability' => $filters->availability?->label() ?? 'All on-hand stock',
        ];

        if ($filters->search !== null && $filters->search !== '') {
            $labels['Search'] = $filters->search;
        }

        return $labels;
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
