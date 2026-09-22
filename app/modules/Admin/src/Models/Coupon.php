<?php

namespace DA\Admin\Models;

use DA\Admin\Database\Factories\CouponFactory;
use DA\Admin\Enums\CouponDiscountType;
use DA\Admin\Models\Queries\CouponQueries;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'code',
    'name',
    'description',
    'discount_type',
    'discount_value',
    'currency_id',
    'max_redemptions',
    'max_redemptions_per_tenant',
    'minimum_amount',
    'starts_at',
    'ends_at',
    'is_active',
])]
class Coupon extends Model
{
    /** @use HasFactory<CouponFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_active' => true,
    ];

    public static function queries(): CouponQueries
    {
        return new CouponQueries(static::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'discount_type' => CouponDiscountType::class,
            'discount_value' => 'decimal:2',
            'minimum_amount' => 'decimal:2',
            'max_redemptions' => 'integer',
            'max_redemptions_per_tenant' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Currency, $this>
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * @return HasMany<CouponRedemption, $this>
     */
    public function redemptions(): HasMany
    {
        return $this->hasMany(CouponRedemption::class);
    }

    /**
     * @param  Builder<Coupon>  $query
     * @return Builder<Coupon>
     */
    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function isPercentage(): bool
    {
        return $this->discount_type === CouponDiscountType::Percentage;
    }

    public function isFixed(): bool
    {
        return $this->discount_type === CouponDiscountType::Fixed;
    }

    public function isActive(): bool
    {
        return $this->is_active === true;
    }

    protected static function newFactory(): CouponFactory
    {
        return CouponFactory::new();
    }
}
