<?php

namespace DA\Inventory\Enums;

enum ProfitReportSortColumn: string
{
    case Sku = 'sku';
    case ProductName = 'product_name';
    case SoldQuantity = 'sold_quantity';
    case NetSales = 'net_sales';
    case Cogs = 'cogs';
    case GrossProfit = 'gross_profit';
    case GrossMargin = 'gross_margin';

    public function label(): string
    {
        return match ($this) {
            self::Sku => 'SKU',
            self::ProductName => 'Product name',
            self::SoldQuantity => 'Sold quantity',
            self::NetSales => 'Net sales',
            self::Cogs => 'Cost of goods sold',
            self::GrossProfit => 'Gross profit',
            self::GrossMargin => 'Gross margin',
        };
    }
}
