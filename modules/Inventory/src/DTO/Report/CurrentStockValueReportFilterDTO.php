<?php

namespace DA\Inventory\DTO\Report;

use DA\Inventory\Enums\CurrentStockAvailability;
use DA\Inventory\Enums\CurrentStockValueReportSortColumn;
use Spatie\LaravelData\Data;

class CurrentStockValueReportFilterDTO extends Data
{
    public function __construct(
        public ?int $storeId,
        public ?int $categoryId,
        public ?int $brandId,
        public ?CurrentStockAvailability $availability,
        public ?string $search,
        public CurrentStockValueReportSortColumn $sortBy,
        public string $sortDirection,
    ) {}
}
