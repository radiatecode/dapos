<?php

namespace DA\Inventory\DTO\Report;

use DA\Inventory\Services\TenantNumberFormatter;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;

class CurrentStockValueReportExportDTO extends Data
{
    /**
     * @param  array<string, string>  $filterLabels
     * @param  Collection<int, object>  $rows
     * @param  array{
     *     available_products: int,
     *     total_available_quantity: string,
     *     total_on_hand_quantity: string,
     *     total_stock_value: string
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
        return 'current-stock-value-report-'.now()->format('Y-m-d-His').'.'.$extension;
    }
}
