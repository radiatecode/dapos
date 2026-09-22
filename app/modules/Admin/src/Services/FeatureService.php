<?php

namespace DA\Admin\Services;

use DA\Admin\Exceptions\FeatureUnavailableException;
use DA\Admin\Exceptions\LimitExceededException;
use DA\Admin\Exceptions\SubscriptionExpiredException;
use DA\Admin\Exceptions\SubscriptionMissingException;
use DA\Admin\Models\Feature;
use DA\Admin\Models\PlanFeature;
use DA\Admin\Models\Subscription;
use DA\Admin\Models\Tenant;
use DA\Admin\Services\Entitlements\ResourceUsageRegistry;
use InvalidArgumentException;

class FeatureService
{
    public function __construct(
        private UsageService $usageService,
        private ResourceUsageRegistry $resourceUsage,
    ) {}

    public function hasFeature(Tenant|Subscription $subject, string $code): bool
    {
        $entitlement = $this->entitlement($this->usableSubscription($subject), $code);

        if ($entitlement === null) {
            return false;
        }

        [$feature, $planFeature] = $entitlement;

        if ($feature->isBoolean()) {
            return $this->booleanEnabled($planFeature);
        }

        return true;
    }

    public function can(Tenant|Subscription $subject, string $code): bool
    {
        $subscription = $this->usableSubscription($subject);
        $entitlement = $this->entitlement($subscription, $code);

        if ($entitlement === null) {
            return false;
        }

        [$feature, $planFeature] = $entitlement;

        if ($feature->isBoolean()) {
            return $this->booleanEnabled($planFeature);
        }

        return $this->hasRemaining($subscription, $feature, $planFeature, 1);
    }

    public function limit(Tenant|Subscription $subject, string $code): ?int
    {
        $entitlement = $this->entitlement($this->usableSubscription($subject), $code);

        if ($entitlement === null) {
            return 0;
        }

        [$feature, $planFeature] = $entitlement;

        if ($feature->isBoolean()) {
            return $this->booleanEnabled($planFeature) ? 1 : 0;
        }

        if ($planFeature->is_unlimited) {
            return null;
        }

        return max(0, (int) $planFeature->value);
    }

    public function isUnlimited(Tenant|Subscription $subject, string $code): bool
    {
        $entitlement = $this->entitlement($this->usableSubscription($subject), $code);

        if ($entitlement === null) {
            return false;
        }

        [$feature, $planFeature] = $entitlement;

        return $feature->isLimit() && $planFeature->is_unlimited === true;
    }

    public function canConsume(Tenant|Subscription $subject, string $code, int $amount = 1): bool
    {
        $this->assertPositiveAmount($amount);

        $subscription = $this->usableSubscription($subject);
        $entitlement = $this->entitlement($subscription, $code);

        if ($entitlement === null) {
            return false;
        }

        [$feature, $planFeature] = $entitlement;

        if ($feature->isBoolean()) {
            return $this->booleanEnabled($planFeature);
        }

        return $this->hasRemaining($subscription, $feature, $planFeature, $amount);
    }

    public function consume(Tenant|Subscription $subject, string $code, int $amount = 1): void
    {
        $this->assertPositiveAmount($amount);

        $subscription = $this->usableSubscription($subject);
        [$feature, $planFeature] = $this->requireEntitlement($subscription, $code);

        if ($feature->isBoolean()) {
            if (! $this->booleanEnabled($planFeature)) {
                throw new FeatureUnavailableException;
            }

            return;
        }

        if (! $this->hasRemaining($subscription, $feature, $planFeature, $amount)) {
            throw new LimitExceededException("The plan limit for {$code} has been reached.");
        }

        if ($feature->isConsumptionLimit()) {
            $this->usageService->increment($subscription, $feature, $amount);
        }
    }

    public function release(Tenant|Subscription $subject, string $code, int $amount = 1): void
    {
        $this->assertPositiveAmount($amount);

        $subscription = $this->usableSubscription($subject);
        [$feature] = $this->requireEntitlement($subscription, $code);

        if ($feature->isConsumptionLimit()) {
            $this->usageService->decrement($subscription, $feature, $amount);
        }
    }

    public function currentUsage(Tenant|Subscription $subject, string $code): int
    {
        $subscription = $this->usableSubscription($subject);
        $entitlement = $this->entitlement($subscription, $code);

        if ($entitlement === null) {
            return 0;
        }

        [$feature] = $entitlement;

        return $this->usageOf($subscription, $feature);
    }

    private function usageOf(Subscription $subscription, Feature $feature): int
    {
        if ($feature->isBoolean()) {
            return 0;
        }

        if ($feature->isResourceLimit()) {
            return $this->resourceUsage->count($feature->code, $this->tenantOf($subscription));
        }

        return $this->usageService->current($subscription, $feature);
    }

    private function hasRemaining(Subscription $subscription, Feature $feature, PlanFeature $planFeature, int $amount): bool
    {
        if ($planFeature->is_unlimited) {
            return true;
        }

        $limit = max(0, (int) $planFeature->value);

        return ($this->usageOf($subscription, $feature) + $amount) <= $limit;
    }

    /**
     * @return array{0: Feature, 1: PlanFeature}|null
     */
    private function entitlement(Subscription $subscription, string $code): ?array
    {
        $feature = Feature::queries()->findByCode($code);

        if (! $feature instanceof Feature) {
            return null;
        }

        $subscription->loadMissing('plan.planFeatures');

        $planFeature = $subscription->plan?->planFeatures->firstWhere('feature_id', $feature->id);

        if (! $planFeature instanceof PlanFeature) {
            return null;
        }

        return [$feature, $planFeature];
    }

    /**
     * @return array{0: Feature, 1: PlanFeature}
     */
    private function requireEntitlement(Subscription $subscription, string $code): array
    {
        $entitlement = $this->entitlement($subscription, $code);

        if ($entitlement === null) {
            throw new FeatureUnavailableException;
        }

        return $entitlement;
    }

    private function usableSubscription(Tenant|Subscription $subject): Subscription
    {
        if ($subject instanceof Subscription) {
            $this->assertUsable($subject);

            return $subject;
        }

        $current = $subject->subscription;

        if ($current instanceof Subscription) {
            $this->assertUsable($current);

            return $current;
        }

        $latest = $subject->subscriptions()->orderByDesc('id')->first();

        if ($latest instanceof Subscription && $latest->isEnded()) {
            throw new SubscriptionExpiredException;
        }

        throw new SubscriptionMissingException;
    }

    private function assertUsable(Subscription $subscription): void
    {
        if ($subscription->isEnded()) {
            throw new SubscriptionExpiredException;
        }
    }

    private function tenantOf(Subscription $subscription): Tenant
    {
        $tenant = $subscription->tenant;

        if ($tenant instanceof Tenant) {
            return $tenant;
        }

        return Tenant::query()->findOrFail($subscription->tenant_id);
    }

    private function booleanEnabled(PlanFeature $planFeature): bool
    {
        if ($planFeature->is_unlimited) {
            return true;
        }

        return in_array($planFeature->value, ['1', 'true', 1, true], true);
    }

    private function assertPositiveAmount(int $amount): void
    {
        if ($amount < 1) {
            throw new InvalidArgumentException('Usage amount must be at least 1.');
        }
    }
}
