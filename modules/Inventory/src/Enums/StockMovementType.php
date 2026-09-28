<?php

namespace DA\Inventory\Enums;

enum StockMovementType: string
{
    case GoodsReceipt = 'goods_receipt';
    case OpeningStock = 'opening_stock';
    case Adjustment = 'adjustment';
    case Transfer = 'transfer';
    case Sale = 'sale';
    case PurchaseReturn = 'purchase_return';
    case CustomerReturn = 'customer_return';

    public function label(): string
    {
        return match ($this) {
            self::GoodsReceipt => 'Goods receipt',
            self::OpeningStock => 'Opening stock',
            self::Adjustment => 'Adjustment',
            self::Transfer => 'Transfer',
            self::Sale => 'Sale',
            self::PurchaseReturn => 'Purchase return',
            self::CustomerReturn => 'Customer return',
        };
    }
}
