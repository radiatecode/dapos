<?php

namespace DA\Inventory\Enums;

enum SalesReportSortColumn: string
{
    case Sku = 'sku';
    case ProductName = 'product_name';
    case SoldQuantity = 'sold_quantity';
    case SoldAmount = 'sold_amount';
    case PaidAmount = 'paid_amount';

    public function label(): string
    {
        return match ($this) {
            self::Sku => 'SKU',
            self::ProductName => 'Product name',
            self::SoldQuantity => 'Sold quantity',
            self::SoldAmount => 'Sold amount',
            self::PaidAmount => 'Paid amount',
        };
    }
}
