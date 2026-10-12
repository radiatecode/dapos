<?php

namespace DA\Inventory\DTO\Report;

use DA\Inventory\Enums\MinStockReportSortColumn;
use DA\Inventory\Enums\MinStockStatus;
use Spatie\LaravelData\Data;

class MinStockReportFilterDTO extends Data
{
    public function __construct(
        public ?int $storeId,
        public ?int $categoryId,
        public ?int $brandId,
        public ?MinStockStatus $stockStatus,
        public ?string $search,
        public MinStockReportSortColumn $sortBy,
        public string $sortDirection,
    ) {}
}
