<?php

namespace DA\Inventory\DTO\Report;

use DA\Inventory\Services\TenantNumberFormatter;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;

class MinStockReportExportDTO extends Data
{
    /**
     * @param  array<string, string>  $filterLabels
     * @param  Collection<int, object>  $rows
     * @param  array{
     *     total_items: int,
     *     out_of_stock_items: int,
     *     low_stock_items: int,
     *     shortage_quantity: string
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
        return 'min-stock-report-'.now()->format('Y-m-d-His').'.'.$extension;
    }
}
