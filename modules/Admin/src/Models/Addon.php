<?php

namespace DA\Admin\Models;

use DA\Admin\Database\Factories\AddonFactory;
use DA\Admin\Enums\BillingInterval;
use DA\Admin\Models\Queries\AddonQueries;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'name',
    'code',
    'description',
    'billing_interval',
    'price',
    'currency_id',
    'is_active',
])]
class Addon extends Model
{
    /** @use HasFactory<AddonFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_active' => true,
    ];

    public static function queries(): AddonQueries
    {
        return new AddonQueries(static::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'billing_interval' => BillingInterval::class,
            'price' => 'decimal:2',
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
     * @param  Builder<Addon>  $query
     * @return Builder<Addon>
     */
    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function isActive(): bool
    {
        return $this->is_active === true;
    }

    protected static function newFactory(): AddonFactory
    {
        return AddonFactory::new();
    }
}
