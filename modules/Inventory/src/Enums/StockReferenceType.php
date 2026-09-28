<?php

namespace DA\Inventory\Enums;

enum StockReferenceType: string
{
    case Purchase = 'purchase';
    case Sale = 'sale';
    case PurchaseReturn = 'purchase_return';
    case CustomerReturn = 'customer_return';
    case StockAdjustment = 'stock_adjustment';
    case StockTransfer = 'stock_transfer';
    case Product = 'product';

    public function label(): string
    {
        return match ($this) {
            self::Purchase => 'Purchase',
            self::Sale => 'Sale',
            self::PurchaseReturn => 'Purchase return',
            self::CustomerReturn => 'Customer return',
            self::StockAdjustment => 'Stock adjustment',
            self::StockTransfer => 'Stock transfer',
            self::Product => 'Product',
        };
    }
}
