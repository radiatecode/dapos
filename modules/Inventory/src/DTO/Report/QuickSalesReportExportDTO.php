<?php

namespace DA\Inventory\DTO\Report;

use DA\Inventory\Services\Decimal;
use DA\Inventory\Services\TenantNumberFormatter;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;

class QuickSalesReportExportDTO extends Data
{
    /**
     * @param  array<string, string>  $filterLabels
     * @param  Collection<int, object>  $rows
     * @param  array{
     *     total_quick_sales: int,
     *     total_items: int,
     *     total_amount: string,
     *     cash_amount: string,
     *     card_amount: string,
     *     bank_transfer_amount: string,
     *     mobile_banking_amount: string,
     *     average_amount: string
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
        return 'quick-sales-report-'.now()->format('Y-m-d-His').'.'.$extension;
    }

    public function nonCashAmount(): string
    {
        return Decimal::add(
            Decimal::add($this->totals['card_amount'], $this->totals['bank_transfer_amount']),
            $this->totals['mobile_banking_amount'],
        );
    }
}
