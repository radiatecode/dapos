<?php

namespace DA\Inventory\DTO\Report;

use DA\Inventory\Enums\ProfitReportSortColumn;
use Spatie\LaravelData\Data;

class ProfitReportFilterDTO extends Data
{
    public function __construct(
        public ?string $dateFrom,
        public ?string $dateTo,
        public ?int $storeId,
        public ?int $categoryId,
        public ?int $brandId,
        public ?string $search,
        public ProfitReportSortColumn $sortBy,
        public string $sortDirection,
    ) {}
}
