<?php

namespace DA\Inventory\DTO\Report;

use DA\Inventory\Services\Decimal;
use DA\Inventory\Services\TenantNumberFormatter;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;

class SalesReportExportDTO extends Data
{
    /**
     * @param  array<string, string>  $filterLabels
     * @param  Collection<int, object>  $rows
     * @param  array{total_products: int, sold_quantity: string, sold_amount: string, paid_amount: string, due_amount: string}  $totals
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
        return 'sales-report-'.now()->format('Y-m-d-His').'.'.$extension;
    }

    public function collectedPercent(): string
    {
        if (Decimal::isZero($this->totals['sold_amount'])) {
            return '0%';
        }

        $percent = ((float) $this->totals['paid_amount'] / (float) $this->totals['sold_amount']) * 100;
        $formatted = number_format($percent, 1, '.', '');

        return rtrim(rtrim($formatted, '0'), '.').'%';
    }
}
