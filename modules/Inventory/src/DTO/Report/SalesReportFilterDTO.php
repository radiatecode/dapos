<?php

namespace DA\Inventory\DTO\Report;

use DA\Inventory\Enums\SalesReportSortColumn;
use Spatie\LaravelData\Data;

class SalesReportFilterDTO extends Data
{
    public function __construct(
        public ?string $dateFrom,
        public ?string $dateTo,
        public ?int $storeId,
        public ?int $categoryId,
        public ?int $brandId,
        public ?string $search,
        public SalesReportSortColumn $sortBy,
        public string $sortDirection,
    ) {}
}
