<?php

namespace DA\Admin\Enums;

enum SubscriptionEventType: string
{
    case Created = 'CREATED';
    case TrialStarted = 'TRIAL_STARTED';
    case Activated = 'ACTIVATED';
    case Paused = 'PAUSED';
    case Resumed = 'RESUMED';
    case CancellationScheduled = 'CANCELLATION_SCHEDULED';
    case Cancelled = 'CANCELLED';
    case Expired = 'EXPIRED';
    case PastDue = 'PAST_DUE';
    case GraceStarted = 'GRACE_STARTED';
    case GraceDaysUpdated = 'GRACE_DAYS_UPDATED';
    case AddonAdded = 'ADDON_ADDED';
    case AddonQuantityUpdated = 'ADDON_QUANTITY_UPDATED';
    case AddonRemoved = 'ADDON_REMOVED';
    case PlanUpgraded = 'PLAN_UPGRADED';
    case PlanDowngraded = 'PLAN_DOWNGRADED';
    case PlanChanged = 'PLAN_CHANGED';

    public function label(): string
    {
        return match ($this) {
            self::Created => 'Created',
            self::TrialStarted => 'Trial started',
            self::Activated => 'Activated',
            self::Paused => 'Paused',
            self::Resumed => 'Resumed',
            self::CancellationScheduled => 'Cancellation scheduled',
            self::Cancelled => 'Cancelled',
            self::Expired => 'Expired',
            self::PastDue => 'Marked past due',
            self::GraceStarted => 'Grace period started',
            self::GraceDaysUpdated => 'Grace days updated',
            self::AddonAdded => 'Add-on added',
            self::AddonQuantityUpdated => 'Add-on quantity updated',
            self::AddonRemoved => 'Add-on removed',
            self::PlanUpgraded => 'Plan upgraded',
            self::PlanDowngraded => 'Plan downgraded',
            self::PlanChanged => 'Plan changed',
        };
    }

    public function isPlanChange(): bool
    {
        return in_array($this, [
            self::PlanUpgraded,
            self::PlanDowngraded,
            self::PlanChanged,
        ], true);
    }
}
