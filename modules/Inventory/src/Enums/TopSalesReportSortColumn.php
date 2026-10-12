<?php

namespace DA\Inventory\Enums;

enum TopSalesReportSortColumn: string
{
    case Sku = 'sku';
    case ProductName = 'product_name';
    case BrandName = 'brand_name';
    case SoldQuantity = 'sold_quantity';
    case SoldAmount = 'sold_amount';
}
