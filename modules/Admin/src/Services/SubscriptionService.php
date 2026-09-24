<?php

namespace DA\Admin\Services;

use DA\Admin\DTO\CreateSubscriptionDTO;
use DA\Admin\Enums\BillingInterval;
use DA\Admin\Enums\SubscriptionEventType;
use DA\Admin\Enums\SubscriptionItemType;
use DA\Admin\Enums\SubscriptionStatus;
use DA\Admin\Exceptions\InvalidSubscriptionTransitionException;
use DA\Admin\Models\Addon;
use DA\Admin\Models\Plan;
use DA\Admin\Models\Subscription;
use DA\Admin\Models\SubscriptionEvent;
use DA\Admin\Models\Tenant;
use DA\Admin\Notifications\SubscriptionGraceStarted;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class SubscriptionService
{
    public function create(CreateSubscriptionDTO $dto): Subscription
    {
        return $this->transact(function () use ($dto): Subscription {
            $tenant = Tenant::query()->lockForUpdate()->findOrFail($dto->tenant_id);
            $plan = Plan::query()->findOrFail($dto->plan_id);

            if (! $tenant->isActive()) {
                throw new InvalidSubscriptionTransitionException('The selected tenant is not active.');
            }

            if (! $plan->isActive()) {
                throw new InvalidSubscriptionTransitionException('The selected plan is not active.');
            }

            $existing = Subscription::query()
                ->where('tenant_id', $tenant->id)
                ->whereIn('status', [
                    SubscriptionStatus::Trialing->value,
                    SubscriptionStatus::Active->value,
                    SubscriptionStatus::PastDue->value,
                    SubscriptionStatus::Paused->value,
                ])
                ->lockForUpdate()
                ->first();

            if ($existing instanceof Subscription) {
                throw new InvalidSubscriptionTransitionException('This tenant already has a current subscription.');
            }

            $startsAt = now();
            $startTrial = $dto->start_trial && $plan->trial_days > 0;

            $subscription = new Subscription;
            $subscription->tenant_id = $tenant->id;
            $subscription->plan_id = $plan->id;
            $subscription->status = $startTrial ? SubscriptionStatus::Trialing : SubscriptionStatus::Active;
            $subscription->starts_at = $startsAt;
            $subscription->cancel_at_period_end = false;
            $subscription->grace_days = $this->normalizedGraceDays($dto->grace_days);

            if ($startTrial) {
                $trialEndsAt = $startsAt->copy()->addDays($plan->trial_days);
                $subscription->trial_ends_at = $trialEndsAt;
                $subscription->current_period_start = $startsAt;
                $subscription->current_period_end = $trialEndsAt;
            } else {
                $subscription->current_period_start = $startsAt;
                $subscription->current_period_end = $this->periodEnd($startsAt, $plan->billing_interval);
            }

            $subscription->save();

            $subscription->items()->create([
                'item_type' => SubscriptionItemType::Plan,
                'reference_id' => $plan->id,
                'quantity' => 1,
                'unit_price' => $plan->price,
            ]);

            foreach ($this->activeAddons($dto->addon_ids) as $addon) {
                $subscription->items()->create([
                    'item_type' => SubscriptionItemType::Addon,
                    'reference_id' => $addon->id,
                    'quantity' => 1,
                    'unit_price' => $addon->price,
                ]);
            }

            $this->recordEvent(
                $subscription,
                SubscriptionEventType::Created,
                null,
                $subscription->status,
                [
                    'plan_id' => $plan->id,
                    'plan_name' => $plan->name,
                    'start_trial' => $startTrial,
                    'addon_ids' => $dto->addon_ids,
                ],
            );

            if ($startTrial) {
                $this->recordEvent($subscription, SubscriptionEventType::TrialStarted, null, SubscriptionStatus::Trialing, [
                    'trial_ends_at' => $subscription->trial_ends_at?->toIso8601String(),
                ]);
            } else {
                $this->recordEvent($subscription, SubscriptionEventType::Activated, null, SubscriptionStatus::Active);
            }

            return $subscription->refresh();
        });
    }

    public function startTrial(Subscription $subscription): Subscription
    {
        return $this->transact(function () use ($subscription): Subscription {
            $subscription = $this->locked($subscription);
            $plan = $subscription->plan()->firstOrFail();
            $oldStatus = $subscription->status;

            if ($subscription->status === SubscriptionStatus::Trialing) {
                throw new InvalidSubscriptionTransitionException('This subscription is already on trial.');
            }

            if ($subscription->isEnded()) {
                throw new InvalidSubscriptionTransitionException('An ended subscription cannot start a trial.');
            }

            if ($plan->trial_days < 1) {
                throw new InvalidSubscriptionTransitionException('The current plan does not include a trial.');
            }

            $now = now();
            $trialEndsAt = $now->copy()->addDays($plan->trial_days);

            $subscription->status = SubscriptionStatus::Trialing;
            $subscription->trial_ends_at = $trialEndsAt;
            $subscription->current_period_start = $now;
            $subscription->current_period_end = $trialEndsAt;
            $subscription->cancel_at_period_end = false;
            $subscription->cancelled_at = null;
            $subscription->ended_at = null;
            $subscription->paused_at = null;
            $subscription->paused_from_status = null;
            $this->clearGrace($subscription);
            $subscription->save();

            $this->recordEvent($subscription, SubscriptionEventType::TrialStarted, $oldStatus, SubscriptionStatus::Trialing, [
                'trial_ends_at' => $trialEndsAt->toIso8601String(),
            ]);

            return $subscription->refresh();
        });
    }

    public function activate(Subscription $subscription): Subscription
    {
        return $this->transact(function () use ($subscription): Subscription {
            $subscription = $this->locked($subscription);
            $oldStatus = $subscription->status;

            if ($subscription->status === SubscriptionStatus::Active) {
                throw new InvalidSubscriptionTransitionException('This subscription is already active.');
            }

            if (! in_array($subscription->status, [
                SubscriptionStatus::Trialing,
                SubscriptionStatus::PastDue,
            ], true)) {
                throw new InvalidSubscriptionTransitionException('Only trialing or past-due subscriptions can be activated.');
            }

            $plan = $subscription->plan()->firstOrFail();
            $now = now();

            $subscription->status = SubscriptionStatus::Active;
            $subscription->current_period_start = $now;
            $subscription->current_period_end = $this->periodEnd($now, $plan->billing_interval);
            $subscription->cancel_at_period_end = false;
            $subscription->cancelled_at = null;
            $subscription->ended_at = null;
            $subscription->paused_at = null;
            $subscription->paused_from_status = null;
            $this->clearGrace($subscription);
            $subscription->save();

            $this->recordEvent($subscription, SubscriptionEventType::Activated, $oldStatus, SubscriptionStatus::Active);

            return $subscription->refresh();
        });
    }

    public function pause(Subscription $subscription): Subscription
    {
        return $this->transact(function () use ($subscription): Subscription {
            $subscription = $this->locked($subscription);
            $oldStatus = $subscription->status;

            if ($subscription->status === SubscriptionStatus::Paused) {
                throw new InvalidSubscriptionTransitionException('This subscription is already paused.');
            }

            if (! in_array($subscription->status, [
                SubscriptionStatus::Active,
                SubscriptionStatus::Trialing,
                SubscriptionStatus::PastDue,
            ], true)) {
                throw new InvalidSubscriptionTransitionException('This subscription cannot be paused.');
            }

            $subscription->paused_from_status = $oldStatus;
            $subscription->paused_at = now();
            $subscription->status = SubscriptionStatus::Paused;
            $subscription->save();

            $this->recordEvent($subscription, SubscriptionEventType::Paused, $oldStatus, SubscriptionStatus::Paused);

            return $subscription->refresh();
        });
    }

    public function resume(Subscription $subscription): Subscription
    {
        return $this->transact(function () use ($subscription): Subscription {
            $subscription = $this->locked($subscription);
            $oldStatus = $subscription->status;

            if ($subscription->status !== SubscriptionStatus::Paused) {
                throw new InvalidSubscriptionTransitionException('Only a paused subscription can be resumed.');
            }

            $restoreTo = $subscription->paused_from_status ?? SubscriptionStatus::Active;

            if ($restoreTo === SubscriptionStatus::Trialing
                && $subscription->trial_ends_at instanceof Carbon
                && $subscription->trial_ends_at->isPast()) {
                $restoreTo = SubscriptionStatus::Active;
            }

            $subscription->status = $restoreTo;
            $subscription->paused_at = null;
            $subscription->paused_from_status = null;
            $subscription->save();

            $this->recordEvent($subscription, SubscriptionEventType::Resumed, $oldStatus, $restoreTo);

            return $subscription->refresh();
        });
    }

    public function cancel(Subscription $subscription, bool $atPeriodEnd = true): Subscription
    {
        return $this->transact(function () use ($subscription, $atPeriodEnd): Subscription {
            $subscription = $this->locked($subscription);
            $oldStatus = $subscription->status;

            if ($subscription->isEnded()) {
                throw new InvalidSubscriptionTransitionException('This subscription has already ended.');
            }

            $now = now();
            $periodEnd = $subscription->current_period_end;
            $schedule = $atPeriodEnd && $periodEnd instanceof Carbon && $periodEnd->isFuture();

            $subscription->cancelled_at = $now;

            if ($schedule) {
                $subscription->cancel_at_period_end = true;
                $subscription->save();

                $this->recordEvent($subscription, SubscriptionEventType::CancellationScheduled, $oldStatus, $oldStatus, [
                    'cancel_at' => $periodEnd->toIso8601String(),
                ]);

                return $subscription->refresh();
            }

            $subscription->status = SubscriptionStatus::Cancelled;
            $subscription->cancel_at_period_end = false;
            $subscription->ended_at = $now;
            $subscription->paused_at = null;
            $subscription->paused_from_status = null;
            $this->clearGrace($subscription);
            $subscription->save();

            $this->recordEvent($subscription, SubscriptionEventType::Cancelled, $oldStatus, SubscriptionStatus::Cancelled);

            return $subscription->refresh();
        });
    }

    public function expire(Subscription $subscription): Subscription
    {
        return $this->transact(function () use ($subscription): Subscription {
            $subscription = $this->locked($subscription);
            $oldStatus = $subscription->status;

            if ($subscription->status === SubscriptionStatus::Expired) {
                throw new InvalidSubscriptionTransitionException('This subscription has already expired.');
            }

            if ($subscription->status === SubscriptionStatus::Cancelled && $subscription->ended_at !== null) {
                throw new InvalidSubscriptionTransitionException('This subscription has already ended.');
            }

            $subscription->status = SubscriptionStatus::Expired;
            $subscription->ended_at = now();
            $subscription->cancel_at_period_end = false;
            $subscription->paused_at = null;
            $subscription->paused_from_status = null;
            $this->clearGrace($subscription);
            $subscription->save();

            $this->recordEvent($subscription, SubscriptionEventType::Expired, $oldStatus, SubscriptionStatus::Expired);

            return $subscription->refresh();
        });
    }

    public function markPastDue(Subscription $subscription): Subscription
    {
        return $this->enterGrace($subscription, fromPeriodEnd: false);
    }

    public function updateGraceDays(Subscription $subscription, int $graceDays): Subscription
    {
        return $this->transact(function () use ($subscription, $graceDays): Subscription {
            $subscription = $this->locked($subscription);
            $oldStatus = $subscription->status;
            $graceDays = $this->normalizedGraceDays($graceDays);
            $previousDays = $subscription->grace_days;

            if ($subscription->isEnded()) {
                throw new InvalidSubscriptionTransitionException('An ended subscription cannot change its grace window.');
            }

            $subscription->grace_days = $graceDays;

            if ($subscription->status === SubscriptionStatus::PastDue) {
                $fromPeriodEnd = $subscription->current_period_end instanceof Carbon
                    && $subscription->current_period_end->lte(now());
                $subscription->grace_ends_at = $this->graceEndsAt($subscription, $fromPeriodEnd);

                if ($subscription->grace_ends_at->lte(now())) {
                    $subscription->save();

                    return $this->concludeGrace($subscription);
                }
            }

            $subscription->save();

            $this->recordEvent($subscription, SubscriptionEventType::GraceDaysUpdated, $oldStatus, $oldStatus, [
                'old_grace_days' => $previousDays,
                'new_grace_days' => $graceDays,
                'grace_ends_at' => $subscription->grace_ends_at?->toIso8601String(),
            ]);

            return $subscription->refresh();
        });
    }

    public function addAddon(Subscription $subscription, Addon $addon, int $quantity = 1): Subscription
    {
        return $this->transact(function () use ($subscription, $addon, $quantity): Subscription {
            $subscription = $this->locked($subscription);

            if (! $subscription->isCurrent()) {
                throw new InvalidSubscriptionTransitionException('Add-ons can only be added to a current subscription.');
            }

            $addon = Addon::query()->lockForUpdate()->findOrFail($addon->id);

            if (! $addon->isActive()) {
                throw new InvalidSubscriptionTransitionException('The selected add-on is not active.');
            }

            $exists = $subscription->items()
                ->where('item_type', SubscriptionItemType::Addon->value)
                ->where('reference_id', $addon->id)
                ->exists();

            if ($exists) {
                throw new InvalidSubscriptionTransitionException('This add-on is already on the subscription.');
            }

            $subscription->items()->create([
                'item_type' => SubscriptionItemType::Addon,
                'reference_id' => $addon->id,
                'quantity' => max(1, $quantity),
                'unit_price' => $addon->price,
            ]);

            $this->recordEvent($subscription, SubscriptionEventType::AddonAdded, $subscription->status, $subscription->status, [
                'addon_id' => $addon->id,
                'addon_name' => $addon->name,
                'quantity' => max(1, $quantity),
                'unit_price' => $addon->price,
            ]);

            return $subscription->refresh();
        });
    }

    public function updateAddonQuantity(Subscription $subscription, Addon $addon, int $quantity): Subscription
    {
        return $this->transact(function () use ($subscription, $addon, $quantity): Subscription {
            $subscription = $this->locked($subscription);
            $quantity = max(1, min(100, $quantity));

            if (! $subscription->isCurrent()) {
                throw new InvalidSubscriptionTransitionException('Add-on quantity can only be changed on a current subscription.');
            }

            $item = $subscription->items()
                ->where('item_type', SubscriptionItemType::Addon->value)
                ->where('reference_id', $addon->id)
                ->first();

            if ($item === null) {
                throw new InvalidSubscriptionTransitionException('This add-on is not on the subscription.');
            }

            $previousQuantity = $item->quantity;

            if ($previousQuantity === $quantity) {
                return $subscription->refresh();
            }

            $item->quantity = $quantity;
            $item->save();

            $this->recordEvent($subscription, SubscriptionEventType::AddonQuantityUpdated, $subscription->status, $subscription->status, [
                'addon_id' => $addon->id,
                'addon_name' => $addon->name,
                'old_quantity' => $previousQuantity,
                'new_quantity' => $quantity,
            ]);

            return $subscription->refresh();
        });
    }

    public function removeAddon(Subscription $subscription, Addon $addon): Subscription
    {
        return $this->transact(function () use ($subscription, $addon): Subscription {
            $subscription = $this->locked($subscription);

            if (! $subscription->isCurrent()) {
                throw new InvalidSubscriptionTransitionException('Add-ons can only be removed from a current subscription.');
            }

            $item = $subscription->items()
                ->where('item_type', SubscriptionItemType::Addon->value)
                ->where('reference_id', $addon->id)
                ->first();

            if ($item === null) {
                throw new InvalidSubscriptionTransitionException('This add-on is not on the subscription.');
            }

            $item->delete();

            $this->recordEvent($subscription, SubscriptionEventType::AddonRemoved, $subscription->status, $subscription->status, [
                'addon_id' => $addon->id,
                'addon_name' => $addon->name,
            ]);

            return $subscription->refresh();
        });
    }

    /**
     * @return array{grace_started: int, concluded: int}
     */
    public function processDuePeriods(): array
    {
        $started = 0;
        $concluded = 0;

        foreach (Subscription::queries()->dueForPeriodClose() as $subscription) {
            $result = $this->enterGrace($subscription, fromPeriodEnd: true);

            if ($result->status === SubscriptionStatus::PastDue) {
                $started++;
            } else {
                $concluded++;
            }
        }

        foreach (Subscription::queries()->dueToConcludeGrace() as $subscription) {
            $this->concludeGrace($subscription);
            $concluded++;
        }

        return [
            'grace_started' => $started,
            'concluded' => $concluded,
        ];
    }

    public function upgrade(Subscription $subscription, Plan $plan): Subscription
    {
        return $this->changePlan($subscription, $plan, 'upgrade');
    }

    public function downgrade(Subscription $subscription, Plan $plan): Subscription
    {
        return $this->changePlan($subscription, $plan, 'downgrade');
    }

    public function changePlan(Subscription $subscription, Plan $plan, ?string $direction = null): Subscription
    {
        return $this->transact(function () use ($subscription, $plan, $direction): Subscription {
            $subscription = $this->locked($subscription);
            $currentPlan = $subscription->plan()->firstOrFail();
            $plan = Plan::query()->lockForUpdate()->findOrFail($plan->id);

            if (! $plan->isActive()) {
                throw new InvalidSubscriptionTransitionException('The selected plan is not active.');
            }

            if ($plan->id === $currentPlan->id) {
                throw new InvalidSubscriptionTransitionException('The subscription is already on this plan.');
            }

            if (! in_array($subscription->status, [
                SubscriptionStatus::Active,
                SubscriptionStatus::Trialing,
            ], true)) {
                throw new InvalidSubscriptionTransitionException('The plan can only be changed while the subscription is active or trialing.');
            }

            $resolved = $this->planChangeDirection($currentPlan, $plan);

            if ($direction === 'upgrade' && $resolved !== SubscriptionEventType::PlanUpgraded) {
                throw new InvalidSubscriptionTransitionException('The selected plan is not an upgrade.');
            }

            if ($direction === 'downgrade' && $resolved !== SubscriptionEventType::PlanDowngraded) {
                throw new InvalidSubscriptionTransitionException('The selected plan is not a downgrade.');
            }

            $oldStatus = $subscription->status;
            $subscription->plan_id = $plan->id;
            $subscription->save();

            $subscription->items()
                ->where('item_type', SubscriptionItemType::Plan->value)
                ->update([
                    'reference_id' => $plan->id,
                    'unit_price' => $plan->price,
                ]);

            $this->recordEvent($subscription, $resolved, $oldStatus, $oldStatus, [
                'old_plan_id' => $currentPlan->id,
                'old_plan_name' => $currentPlan->name,
                'old_plan_price' => $currentPlan->price,
                'new_plan_id' => $plan->id,
                'new_plan_name' => $plan->name,
                'new_plan_price' => $plan->price,
            ]);

            return $subscription->refresh();
        });
    }

    private function planChangeDirection(Plan $current, Plan $next): SubscriptionEventType
    {
        $currentPrice = (float) $current->price;
        $nextPrice = (float) $next->price;

        if ($nextPrice > $currentPrice) {
            return SubscriptionEventType::PlanUpgraded;
        }

        if ($nextPrice < $currentPrice) {
            return SubscriptionEventType::PlanDowngraded;
        }

        return SubscriptionEventType::PlanChanged;
    }

    /**
     * @param  list<int>  $addonIds
     * @return list<Addon>
     */
    private function activeAddons(array $addonIds): array
    {
        if ($addonIds === []) {
            return [];
        }

        $addons = Addon::query()
            ->whereIn('id', $addonIds)
            ->where('is_active', true)
            ->orderBy('id')
            ->get();

        if ($addons->count() !== count(array_unique($addonIds))) {
            throw new InvalidSubscriptionTransitionException('One or more selected add-ons are invalid or inactive.');
        }

        return $addons->all();
    }

    /**
     * @template TReturn
     *
     * @param  callable(): TReturn  $callback
     * @return TReturn
     */
    private function enterGrace(Subscription $subscription, bool $fromPeriodEnd): Subscription
    {
        $result = $this->transact(function () use ($subscription, $fromPeriodEnd): Subscription {
            $subscription = $this->locked($subscription);
            $oldStatus = $subscription->status;

            if ($subscription->status === SubscriptionStatus::PastDue && $subscription->grace_ends_at !== null) {
                return $subscription;
            }

            $allowed = $fromPeriodEnd
                ? [SubscriptionStatus::Active, SubscriptionStatus::Trialing]
                : [SubscriptionStatus::Active];

            if (! in_array($subscription->status, $allowed, true)) {
                throw new InvalidSubscriptionTransitionException(
                    $fromPeriodEnd
                        ? 'This subscription is not due for a grace period.'
                        : 'Only an active subscription can be marked past due.'
                );
            }

            $graceEndsAt = $this->graceEndsAt($subscription, $fromPeriodEnd);

            if ($subscription->grace_days < 1 || $graceEndsAt->lte(now())) {
                return $this->concludeAfterPeriod($subscription);
            }

            $subscription->status = SubscriptionStatus::PastDue;
            $subscription->grace_ends_at = $graceEndsAt;
            $subscription->save();

            $this->recordEvent($subscription, SubscriptionEventType::PastDue, $oldStatus, SubscriptionStatus::PastDue);
            $this->recordEvent($subscription, SubscriptionEventType::GraceStarted, $oldStatus, SubscriptionStatus::PastDue, [
                'grace_days' => $subscription->grace_days,
                'grace_ends_at' => $graceEndsAt->toIso8601String(),
            ]);

            return $subscription->refresh();
        });

        $this->notifyGraceStarted($result);

        return $result->refresh();
    }

    public function concludeGrace(Subscription $subscription): Subscription
    {
        return $this->transact(function () use ($subscription): Subscription {
            $subscription = $this->locked($subscription);

            if ($subscription->status !== SubscriptionStatus::PastDue) {
                return $subscription;
            }

            if ($subscription->grace_ends_at instanceof Carbon && $subscription->grace_ends_at->isFuture()) {
                throw new InvalidSubscriptionTransitionException('The grace period has not ended yet.');
            }

            return $this->concludeAfterPeriod($subscription);
        });
    }

    private function concludeAfterPeriod(Subscription $subscription): Subscription
    {
        if ($subscription->cancel_at_period_end) {
            return $this->cancel($subscription, false);
        }

        return $this->expire($subscription);
    }

    private function graceEndsAt(Subscription $subscription, bool $fromPeriodEnd): Carbon
    {
        $days = $this->normalizedGraceDays($subscription->grace_days);

        if ($fromPeriodEnd && $subscription->current_period_end instanceof Carbon) {
            return $subscription->current_period_end->copy()->addDays($days);
        }

        return now()->addDays($days);
    }

    private function normalizedGraceDays(int $graceDays): int
    {
        return max(0, min(365, $graceDays));
    }

    private function clearGrace(Subscription $subscription): void
    {
        $subscription->grace_ends_at = null;
        $subscription->grace_notified_at = null;
    }

    private function notifyGraceStarted(Subscription $subscription): void
    {
        if ($subscription->status !== SubscriptionStatus::PastDue || $subscription->grace_notified_at !== null) {
            return;
        }

        if (! $subscription->grace_ends_at instanceof Carbon || $subscription->grace_ends_at->isPast()) {
            return;
        }

        $subscription->loadMissing('tenant');
        $email = $subscription->tenant?->notificationEmail();

        if ($email === null) {
            return;
        }

        Notification::route('mail', $email)->notify(new SubscriptionGraceStarted($subscription));

        $subscription->grace_notified_at = now();
        $subscription->save();
    }

    private function transact(callable $callback): mixed
    {
        if (DB::transactionLevel() > 0) {
            return $callback();
        }

        return DB::transaction($callback);
    }

    private function periodEnd(Carbon $start, BillingInterval $interval): Carbon
    {
        return match ($interval) {
            BillingInterval::Monthly => $start->copy()->addMonth(),
            BillingInterval::Yearly => $start->copy()->addYear(),
        };
    }

    private function locked(Subscription $subscription): Subscription
    {
        return Subscription::query()
            ->whereKey($subscription->id)
            ->lockForUpdate()
            ->firstOrFail();
    }

    /**
     * @param  array<string, mixed>|null  $metadata
     */
    private function recordEvent(
        Subscription $subscription,
        SubscriptionEventType $type,
        ?SubscriptionStatus $oldStatus,
        ?SubscriptionStatus $newStatus,
        ?array $metadata = null,
    ): SubscriptionEvent {
        $event = new SubscriptionEvent;
        $event->tenant_id = $subscription->tenant_id;
        $event->subscription_id = $subscription->id;
        $event->event_type = $type;
        $event->old_status = $oldStatus;
        $event->new_status = $newStatus;
        $event->metadata = $metadata;
        $event->occurred_at = now();
        $event->save();

        return $event;
    }
}
