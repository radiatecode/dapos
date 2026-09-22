<?php

namespace DA\Admin\Enums;

enum InvoiceStatus: string
{
    case Open = 'OPEN';
    case Paid = 'PAID';
    case Failed = 'FAILED';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Open',
            self::Paid => 'Paid',
            self::Failed => 'Failed',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Open => 'accent',
            self::Paid => 'success',
            self::Failed => 'danger',
        };
    }

    public function isOpen(): bool
    {
        return $this === self::Open;
    }

    public function isPaid(): bool
    {
        return $this === self::Paid;
    }
}
