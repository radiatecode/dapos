<?php

namespace DA\Inventory\Enums;

enum InventoryLayerSourceType: string
{
    case GoodsReceipt = 'goods_receipt';
    case StockAdjustment = 'stock_adjustment';
    case StockTransfer = 'stock_transfer';
    case OpeningStock = 'opening_stock';
    case CustomerReturn = 'customer_return';

    public function label(): string
    {
        return match ($this) {
            self::GoodsReceipt => 'Goods receipt',
            self::StockAdjustment => 'Stock adjustment',
            self::StockTransfer => 'Stock transfer',
            self::OpeningStock => 'Opening stock',
            self::CustomerReturn => 'Customer return',
        };
    }
}
