<?php

namespace DA\Inventory\DTO\Report;

use DA\Inventory\Enums\TopSalesReportSortColumn;
use Spatie\LaravelData\Data;

class TopSalesReportFilterDTO extends Data
{
    public function __construct(
        public ?string $dateFrom,
        public ?string $dateTo,
        public ?int $storeId,
        public ?int $brandId,
        public ?string $search,
        public TopSalesReportSortColumn $sortBy,
        public string $sortDirection,
    ) {}
}
