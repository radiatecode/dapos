<?php

namespace DA\Admin\Services;

use DA\Admin\Enums\Features;
use DA\Admin\Exceptions\FeatureUnavailableException;
use DA\Admin\Exceptions\LimitExceededException;
use DA\Admin\Exceptions\SubscriptionExpiredException;
use DA\Admin\Models\Subscription;
use DA\Admin\Models\SubscriptionFeature;
use DA\Admin\Models\Tenant;
use DA\Admin\Services\Entitlements\ResourceUsageRegistry;
use InvalidArgumentException;

class FeatureService
{
    public function __construct(
        private UsageService $usageService,
        private ResourceUsageRegistry $resourceUsage,
    ) {}

    public function hasFeature(Subscription $subscription, string $code): bool
    {
        $this->assertUsable($subscription);

        if ($this->isLimitFeature($code)) {
            $hasAllocated = array_values(array_filter($subscription->limit_features, function ($feature) use ($code) {
                return $feature['feature'] === $code;
            }));

            if (empty($hasAllocated)) {
                return false;
            }

            return true;
        }

        return in_array($code, $subscription->features, true);
    }

    public function isLimitFeature(string $code): bool
    {
        $feature = Features::tryFrom($code);

        if (! $feature instanceof Features) {
            return false;
        }

        return $feature->isLimit();
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

    public function canConsume(Subscription $subscription, string $code, int $amount = 1): bool
    {
        $this->assertPositiveAmount($amount);

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

        if ($this->tracksConsumption($feature)) {
            $this->usageService->increment($subscription, $feature->value, $amount);
        }
    }

    public function release(Tenant|Subscription $subject, string $code, int $amount = 1): void
    {
        $this->assertPositiveAmount($amount);

        $subscription = $this->usableSubscription($subject);
        [$feature] = $this->requireEntitlement($subscription, $code);

        if ($this->tracksConsumption($feature)) {
            $this->usageService->decrement($subscription, $feature->value, $amount);
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

    private function usageOf(Subscription $subscription, Features $feature): int
    {
        if ($feature->isBoolean()) {
            return 0;
        }

        if ($this->resourceUsage->has($feature->value)) {
            return $this->resourceUsage->count($feature->value, $this->tenantOf($subscription));
        }

        return $this->usageService->current($subscription, $feature->value);
    }

    private function tracksConsumption(Features $feature): bool
    {
        return $feature->isLimit() && ! $this->resourceUsage->has($feature->value);
    }

    private function hasRemaining(Subscription $subscription, Features $feature, SubscriptionFeature $planFeature, int $amount): bool
    {
        if ($planFeature->is_unlimited) {
            return true;
        }

        $limit = max(0, (int) $planFeature->value);

        return ($this->usageOf($subscription, $feature) + $amount) <= $limit;
    }

    /**
     * @return array{0: Features, 1: SubscriptionFeature}
     */
    private function requireEntitlement(Subscription $subscription, string $code): array
    {
        $entitlement = $this->entitlement($subscription, $code);

        if ($entitlement === null) {
            throw new FeatureUnavailableException;
        }

        return $entitlement;
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

    private function booleanEnabled(SubscriptionFeature $planFeature): bool
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
