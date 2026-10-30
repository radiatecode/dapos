<?php

namespace DA\Expense\Actions\Report;

use DA\Admin\Models\Tenant;
use DA\Expense\DTO\Report\ExpenseReportExportDTO;
use DA\Expense\DTO\Report\ExpenseReportFilterDTO;
use DA\Expense\Models\Expense;
use DA\Expense\Models\ExpenseCategory;
use DA\Inventory\Models\CurrencySetting;
use DA\Inventory\Models\Store;
use DA\Inventory\Services\TenantNumberFormatter;

class PrepareExpenseReportExport
{
    public function handle(ExpenseReportFilterDTO $filters): ExpenseReportExportDTO
    {
        /** @var Tenant $tenant */
        $tenant = Tenant::queries()->findById((int) auth()->user()->tenant_id);

        return new ExpenseReportExportDTO(
            businessName: (string) $tenant->name,
            generatedAt: now()->format('d M Y, h:i A'),
            numbers: TenantNumberFormatter::fromCurrencySetting(
                CurrencySetting::queries()->findForTenant(),
                (string) $tenant->currency,
            ),
            filterLabels: $this->filterLabels($filters),
            rows: Expense::queries()->reportRows($filters),
            totals: Expense::queries()->reportTotals($filters),
        );
    }

    /**
     * The summary matches the report widgets, which stay in place when a status pill filters the list.
     *
     * @return array<string, string>
     */
    private function filterLabels(ExpenseReportFilterDTO $filters): array
    {
        $labels = [
            'Period' => $this->periodLabel($filters),
            'Store' => $this->nameOrAll(Store::queries()->nameForAuthenticatedTenant(...), $filters->storeId, 'All stores'),
            'Category' => $this->nameOrAll(ExpenseCategory::queries()->nameForAuthenticatedTenant(...), $filters->categoryId, 'All categories'),
            'Status' => $filters->status?->label() ?? 'All statuses',
        ];

        if ($filters->search !== null && $filters->search !== '') {
            $labels['Search'] = $filters->search;
        }

        return $labels;
    }

    private function periodLabel(ExpenseReportFilterDTO $filters): string
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
