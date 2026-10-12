<?php

namespace DA\Inventory\Actions\Report;

use DA\Admin\Models\Tenant;
use DA\Inventory\DTO\Report\MinStockReportExportDTO;
use DA\Inventory\DTO\Report\MinStockReportFilterDTO;
use DA\Inventory\Models\Brand;
use DA\Inventory\Models\Category;
use DA\Inventory\Models\CurrencySetting;
use DA\Inventory\Models\ProductVariant;
use DA\Inventory\Models\Store;
use DA\Inventory\Services\TenantNumberFormatter;

class PrepareMinStockReportExport
{
    public function handle(MinStockReportFilterDTO $filters): MinStockReportExportDTO
    {
        /** @var Tenant $tenant */
        $tenant = Tenant::queries()->findById((int) auth()->user()->tenant_id);

        return new MinStockReportExportDTO(
            businessName: (string) $tenant->name,
            generatedAt: now()->format('d M Y, h:i A'),
            numbers: TenantNumberFormatter::fromCurrencySetting(
                CurrencySetting::queries()->findForTenant(),
                (string) $tenant->currency,
            ),
            filterLabels: $this->filterLabels($filters),
            rows: ProductVariant::queries()->minStockReportRows($filters),
            totals: ProductVariant::queries()->minStockReportTotals($filters),
        );
    }

    /**
     * @return array<string, string>
     */
    private function filterLabels(MinStockReportFilterDTO $filters): array
    {
        $labels = [
            'Store' => $this->nameOrAll(Store::queries()->nameForAuthenticatedTenant(...), $filters->storeId, 'All stores'),
            'Category' => $this->nameOrAll(Category::queries()->nameForAuthenticatedTenant(...), $filters->categoryId, 'All categories'),
            'Brand' => $this->nameOrAll(Brand::queries()->nameForAuthenticatedTenant(...), $filters->brandId, 'All brands'),
            'Stock status' => $filters->stockStatus?->label() ?? 'Out of stock and low stock',
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
