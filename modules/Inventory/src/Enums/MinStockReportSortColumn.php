<?php

namespace DA\Inventory\Enums;

enum MinStockReportSortColumn: string
{
    case Sku = 'sku';
    case ProductName = 'product_name';
    case CurrentStock = 'current_stock';
    case MinStockLevel = 'min_stock_level';
    case ShortageQuantity = 'shortage_quantity';

    public function label(): string
    {
        return match ($this) {
            self::Sku => 'SKU',
            self::ProductName => 'Product name',
            self::CurrentStock => 'Current stock',
            self::MinStockLevel => 'Min stock level',
            self::ShortageQuantity => 'Shortage quantity',
        };
    }
}
