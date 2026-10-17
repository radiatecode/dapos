<?php

namespace DA\Inventory\Supports\Product;

final class ProductBatchColumns
{
    public const MAX_ROWS = 500;

    public const HEADER_ROW = 3;

    /**
     * @var list<string>
     */
    public const HEADINGS = [
        'Name',
        'Description',
        'Category',
        'Brand',
        'Unit',
        'SKU',
        'Barcode',
        'Cost Price',
        'Selling Price',
        'Quantity',
        'Min Stock Level',
    ];

    /**
     * @var array<string, string>
     */
    public const KEYS = [
        'Name' => 'name',
        'Description' => 'description',
        'Category' => 'category',
        'Brand' => 'brand',
        'Unit' => 'unit',
        'SKU' => 'sku',
        'Barcode' => 'barcode',
        'Cost Price' => 'cost_price',
        'Selling Price' => 'selling_price',
        'Quantity' => 'quantity',
        'Min Stock Level' => 'min_stock_level',
    ];
}
