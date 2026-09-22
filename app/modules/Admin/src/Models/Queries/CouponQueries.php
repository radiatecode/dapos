<?php

namespace DA\Admin\Models\Queries;

use DA\Admin\Models\Coupon;
use Illuminate\Database\Query\Builder;

class CouponQueries extends BaseQueries
{
    public function findByCode(string $code): ?Coupon
    {
        return $this->eloquentBuilder()
            ->where('code', $code)
            ->first();
    }

    public function datatable(): Builder
    {
        return $this->queryBuilder()
            ->leftJoin('currencies', 'currencies.id', '=', 'coupons.currency_id')
            ->select(
                'coupons.id',
                'coupons.code',
                'coupons.name',
                'coupons.description',
                'coupons.discount_type',
                'coupons.discount_value',
                'coupons.currency_id',
                'coupons.max_redemptions',
                'coupons.max_redemptions_per_tenant',
                'coupons.minimum_amount',
                'coupons.starts_at',
                'coupons.ends_at',
                'coupons.is_active',
                'currencies.code as currency_code',
                'coupons.created_at',
                'coupons.updated_at',
            )
            ->orderBy('coupons.id', 'desc');
    }
}
