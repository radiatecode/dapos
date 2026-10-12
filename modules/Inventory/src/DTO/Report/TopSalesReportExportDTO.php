<?php

namespace DA\Inventory\DTO\Report;

use DA\Inventory\Services\TenantNumberFormatter;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;

class TopSalesReportExportDTO extends Data
{
    /**
     * @param  array<string, string>  $filterLabels
     * @param  Collection<int, object>  $rows
     * @param  array{total_products: int, sold_quantity: string, sold_amount: string}  $totals
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
        return 'top-sales-report-'.now()->format('Y-m-d-His').'.'.$extension;
    }
}
