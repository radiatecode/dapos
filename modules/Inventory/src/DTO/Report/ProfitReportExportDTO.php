<?php

namespace DA\Inventory\DTO\Report;

use DA\Inventory\Services\TenantNumberFormatter;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;

class ProfitReportExportDTO extends Data
{
    /**
     * @param  array<string, string>  $filterLabels
     * @param  Collection<int, object>  $rows
     * @param  array{
     *     total_products: int,
     *     loss_products: int,
     *     sold_quantity: string,
     *     net_sales: string,
     *     cogs: string,
     *     gross_profit: string,
     *     gross_margin: string
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
        return 'profit-report-'.now()->format('Y-m-d-His').'.'.$extension;
    }

    public function lossNote(): string
    {
        return match (true) {
            $this->totals['loss_products'] === 1 => '1 product sold at a loss',
            $this->totals['loss_products'] > 1 => $this->totals['loss_products'].' products sold at a loss',
            default => 'No products sold at a loss',
        };
    }
}
