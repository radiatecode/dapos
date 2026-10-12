<?php

namespace DA\Inventory\Enums;

enum CurrentStockValueReportSortColumn: string
{
    case Sku = 'sku';
    case ProductName = 'product_name';
    case AvailableQuantity = 'available_quantity';
    case OnHandQuantity = 'on_hand_quantity';
    case UnitCost = 'unit_cost';
    case StockValue = 'stock_value';

    public function label(): string
    {
        return match ($this) {
            self::Sku => 'SKU',
            self::ProductName => 'Product name',
            self::AvailableQuantity => 'Available quantity',
            self::OnHandQuantity => 'On-hand quantity',
            self::UnitCost => 'Average cost',
            self::StockValue => 'Stock value',
        };
    }
}
