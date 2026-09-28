<?php

namespace DA\Inventory\Enums;

enum AllocationSourceType: string
{
    case Sale = 'sale';
    case PurchaseReturn = 'purchase_return';
    case StockAdjustment = 'stock_adjustment';
    case StockTransfer = 'stock_transfer';

    public function label(): string
    {
        return match ($this) {
            self::Sale => 'Sale',
            self::PurchaseReturn => 'Purchase return',
            self::StockAdjustment => 'Stock adjustment',
            self::StockTransfer => 'Stock transfer',
        };
    }
}
