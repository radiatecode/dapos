<?php

namespace DA\Inventory\DTO\Report;

use DA\Inventory\Models\Sale;
use DA\Inventory\Services\Decimal;
use DA\Inventory\Services\TenantNumberFormatter;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;

class SalesInvoiceReportExportDTO extends Data
{
    /**
     * @param  array<string, string>  $filterLabels
     * @param  Collection<int, Sale>  $rows
     * @param  array{
     *     total_sales: int,
     *     total_amount: string,
     *     total_paid: string,
     *     total_due: string,
     *     paid_count: int,
     *     unpaid_count: int,
     *     partial_count: int
     * }  $totals
     */
    public function __construct(
        public string $businessName,
        public string $generatedAt,
        public TenantNumberFormatter $numbers,
        public array $filterLabels,
        public Collection $rows,
        public array $totals,
    ) {}

    public function fileName(string $extension): string
    {
        return 'sales-invoice-report-'.now()->format('Y-m-d-His').'.'.$extension;
    }

    public function collectedPercent(): string
    {
        if (Decimal::isZero($this->totals['total_amount'])) {
            return '0%';
        }

        $percent = ((float) $this->totals['total_paid'] / (float) $this->totals['total_amount']) * 100;
        $formatted = number_format($percent, 1, '.', '');

        return rtrim(rtrim($formatted, '0'), '.').'%';
    }

    public function outstandingInvoices(): int
    {
        return $this->totals['unpaid_count'] + $this->totals['partial_count'];
    }
}
