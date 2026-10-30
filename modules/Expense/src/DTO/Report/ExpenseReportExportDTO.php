<?php

namespace DA\Expense\DTO\Report;

use DA\Expense\Models\Expense;
use DA\Inventory\Services\TenantNumberFormatter;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;

class ExpenseReportExportDTO extends Data
{
    /**
     * @param  array<string, string>  $filterLabels
     * @param  Collection<int, Expense>  $rows
     * @param  array{
     *     total_expenses: int,
     *     total_amount: string,
     *     pending_count: int,
     *     pending_amount: string,
     *     approved_count: int,
     *     approved_amount: string,
     *     rejected_count: int,
     *     rejected_amount: string
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
        return 'expense-report-'.now()->format('Y-m-d-His').'.'.$extension;
    }
}
