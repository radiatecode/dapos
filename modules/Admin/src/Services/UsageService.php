<?php

namespace DA\Admin\Services;

use DA\Admin\Models\Feature;
use DA\Admin\Models\Subscription;
use DA\Admin\Models\TenantUsage;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class UsageService
{
    public function current(Subscription $subscription, Feature $feature): int
    {
        $row = $this->find($subscription, $feature);

        return $row instanceof TenantUsage ? $row->usage : 0;
    }

    public function increment(Subscription $subscription, Feature $feature, int $amount = 1): TenantUsage
    {
        return $this->transact(function () use ($subscription, $feature, $amount): TenantUsage {
            $row = $this->lockOrCreate($subscription, $feature);
            $row->usage += $amount;
            $row->save();

            return $row;
        });
    }

    public function decrement(Subscription $subscription, Feature $feature, int $amount = 1): TenantUsage
    {
        return $this->transact(function () use ($subscription, $feature, $amount): TenantUsage {
            $row = $this->lockOrCreate($subscription, $feature);
            $row->usage = max(0, $row->usage - $amount);
            $row->save();

            return $row;
        });
    }

    public function find(Subscription $subscription, Feature $feature): ?TenantUsage
    {
        [$periodStart] = $this->period($subscription);

        return TenantUsage::queries()->forTenantFeaturePeriod(
            $subscription->tenant_id,
            $feature->id,
            $periodStart,
        );
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function period(Subscription $subscription): array
    {
        $start = $subscription->current_period_start ?? now()->startOfMonth();
        $end = $subscription->current_period_end ?? $start->copy()->endOfMonth();

        return [$start, $end];
    }

    private function lockOrCreate(Subscription $subscription, Feature $feature): TenantUsage
    {
        [$periodStart, $periodEnd] = $this->period($subscription);

        $row = TenantUsage::query()
            ->where('tenant_id', $subscription->tenant_id)
            ->where('feature_id', $feature->id)
            ->where('period_start', $periodStart)
            ->lockForUpdate()
            ->first();

        if ($row instanceof TenantUsage) {
            $row->period_end = $periodEnd;

            return $row;
        }

        try {
            return TenantUsage::query()->create([
                'tenant_id' => $subscription->tenant_id,
                'feature_id' => $feature->id,
                'usage' => 0,
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
            ]);
        } catch (UniqueConstraintViolationException) {
            $row = TenantUsage::query()
                ->where('tenant_id', $subscription->tenant_id)
                ->where('feature_id', $feature->id)
                ->where('period_start', $periodStart)
                ->lockForUpdate()
                ->firstOrFail();

            $row->period_end = $periodEnd;

            return $row;
        }
    }

    /**
     * @template TReturn
     *
     * @param  callable(): TReturn  $callback
     * @return TReturn
     */
    private function transact(callable $callback): mixed
    {
        if (DB::transactionLevel() > 0) {
            return $callback();
        }

        return DB::transaction($callback);
    }
}
