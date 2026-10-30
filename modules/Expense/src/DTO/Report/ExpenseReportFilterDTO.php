<?php

namespace DA\Expense\DTO\Report;

use DA\Expense\Enums\ExpenseStatus;
use Spatie\LaravelData\Data;

class ExpenseReportFilterDTO extends Data
{
    public function __construct(
        public ?string $dateFrom,
        public ?string $dateTo,
        public ?int $storeId,
        public ?int $categoryId,
        public ?ExpenseStatus $status,
        public ?string $search,
    ) {}
}
