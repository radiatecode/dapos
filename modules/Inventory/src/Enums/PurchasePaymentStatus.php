<?php

namespace DA\Inventory\Enums;

use DA\Inventory\Services\Decimal;

enum PurchasePaymentStatus: string
{
    case Paid = 'Paid';
    case Unpaid = 'Unpaid';
    case Partial = 'Partial';

    public function label(): string
    {
        return $this->value;
    }

    /**
     * A zero payment is unpaid. An equal payment is paid. Anything between is partial.
     * The paid amount must already be within the grand total.
     */
    public static function fromAmounts(string $grandTotal, string $paidAmount): self
    {
        if (Decimal::isZero($paidAmount)) {
            return self::Unpaid;
        }

        if (Decimal::compare($paidAmount, $grandTotal) === 0) {
            return self::Paid;
        }

        return self::Partial;
    }
}
