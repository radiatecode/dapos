<?php

namespace DA\Inventory\Actions\Report;

use DA\Admin\Models\Tenant;
use DA\Inventory\DTO\Report\SalesInvoiceReportExportDTO;
use DA\Inventory\DTO\Report\SalesInvoiceReportFilterDTO;
use DA\Inventory\Models\CurrencySetting;
use DA\Inventory\Models\Customer;
use DA\Inventory\Models\Sale;
use DA\Inventory\Models\Store;
use DA\Inventory\Services\TenantNumberFormatter;

class PrepareSalesInvoiceReportExport
{
    public function handle(SalesInvoiceReportFilterDTO $filters): SalesInvoiceReportExportDTO
    {
        /** @var Tenant $tenant */
        $tenant = Tenant::queries()->findById((int) auth()->user()->tenant_id);

        return new SalesInvoiceReportExportDTO(
            businessName: (string) $tenant->name,
            generatedAt: now()->format('d M Y, h:i A'),
            numbers: TenantNumberFormatter::fromCurrencySetting(
                CurrencySetting::queries()->findForTenant(),
                (string) $tenant->currency,
            ),
            filterLabels: $this->filterLabels($filters),
            rows: Sale::queries()->invoiceReportRows(
                paymentStatus: $filters->paymentStatus,
                customerId: $filters->customerId,
                storeId: $filters->storeId,
                search: $filters->search,
                dateFrom: $filters->dateFrom,
                dateTo: $filters->dateTo,
            ),
            totals: Sale::queries()->summary(
                customerId: $filters->customerId,
                storeId: $filters->storeId,
                search: $filters->search,
                dateFrom: $filters->dateFrom,
                dateTo: $filters->dateTo,
            ),
        );
    }

    /**
     * The summary matches the sales page widgets, which stay in place when a
     * payment preset filters the invoice list.
     *
     * @return array<string, string>
     */
    private function filterLabels(SalesInvoiceReportFilterDTO $filters): array
    {
        $labels = [
            'Period' => $this->periodLabel($filters),
            'Store' => $this->nameOrAll(Store::queries()->nameForAuthenticatedTenant(...), $filters->storeId, 'All stores'),
            'Customer' => $this->nameOrAll(Customer::queries()->nameForAuthenticatedTenant(...), $filters->customerId, 'All customers'),
            'Payment' => $filters->paymentStatus?->value ?? 'All payments',
        ];

        if ($filters->search !== null && $filters->search !== '') {
            $labels['Search'] = $filters->search;
        }

        return $labels;
    }

    private function periodLabel(SalesInvoiceReportFilterDTO $filters): string
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
