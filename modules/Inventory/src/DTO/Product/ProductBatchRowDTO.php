<?php

namespace DA\Inventory\DTO\Product;

use Spatie\LaravelData\Data;

class ProductBatchRowDTO extends Data
{
    public function __construct(
        public int $excelRow,
        public string $name,
        public string $description,
        public string $category,
        public string $brand,
        public string $unit,
        public string $sku,
        public string $barcode,
        public string $costPrice,
        public string $sellingPrice,
        public string $quantity,
        public string $minStockLevel,
    ) {}

    /**
     * @return array<string, string>
     */
    public function values(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'category' => $this->category,
            'brand' => $this->brand,
            'unit' => $this->unit,
            'sku' => $this->sku,
            'barcode' => $this->barcode,
            'cost_price' => $this->costPrice,
            'selling_price' => $this->sellingPrice,
            'quantity' => $this->quantity,
            'min_stock_level' => $this->minStockLevel,
        ];
    }
}
