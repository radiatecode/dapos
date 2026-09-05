<?php

namespace DA\Admin\Enums;

enum SubscriptionStatus: string
{
    case Trialing = 'TRIALING';
    case Active = 'ACTIVE';
    case PastDue = 'PAST_DUE';
    case Paused = 'PAUSED';
    case Cancelled = 'CANCELLED';
    case Expired = 'EXPIRED';

    public function label(): string
    {
        return match ($this) {
            self::Trialing => 'Trialing',
            self::Active => 'Active',
            self::PastDue => 'Past due',
            self::Paused => 'Paused',
            self::Cancelled => 'Cancelled',
            self::Expired => 'Expired',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Trialing => 'accent',
            self::Active => 'success',
            self::PastDue, self::Cancelled => 'danger',
            self::Paused, self::Expired => 'muted',
        };
    }

    public function isCurrent(): bool
    {
        return in_array($this, [
            self::Trialing,
            self::Active,
            self::PastDue,
            self::Paused,
        ], true);
    }

    public function isEnded(): bool
    {
        return in_array($this, [self::Cancelled, self::Expired], true);
    }
}
