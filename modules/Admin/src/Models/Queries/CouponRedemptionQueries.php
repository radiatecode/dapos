<?php

namespace DA\Admin\Models\Queries;

use DA\Admin\Models\CouponRedemption;
use Illuminate\Database\Eloquent\Collection;

class CouponRedemptionQueries extends BaseQueries
{
    public function countForCoupon(int $couponId): int
    {
        return $this->eloquentBuilder()
            ->where('coupon_id', $couponId)
            ->count();
    }

    public function countForCouponAndTenant(int $couponId, int $tenantId): int
    {
        return $this->eloquentBuilder()
            ->where('coupon_id', $couponId)
            ->where('tenant_id', $tenantId)
            ->count();
    }

    /**
     * @return Collection<int, CouponRedemption>
     */
    public function forTenant(int $tenantId): Collection
    {
        return $this->eloquentBuilder()
            ->where('tenant_id', $tenantId)
            ->orderByDesc('id')
            ->get();
    }
}
