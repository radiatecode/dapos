<?php

namespace DA\Admin\Models;

use DA\Admin\Database\Factories\FeatureFactory;
use DA\Admin\Enums\FeatureEnforcement;
use DA\Admin\Enums\FeatureType;
use DA\Admin\Models\Queries\FeatureQueries;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'code',
    'type',
    'enforcement',
    'description',
])]
class Feature extends Model
{
    /** @use HasFactory<FeatureFactory> */
    use HasFactory;

    public static function queries(): FeatureQueries
    {
        return new FeatureQueries(static::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => FeatureType::class,
            'enforcement' => FeatureEnforcement::class,
        ];
    }

    /**
     * @return HasMany<PlanFeature, $this>
     */
    public function planFeatures(): HasMany
    {
        return $this->hasMany(PlanFeature::class);
    }

    /**
     * @return BelongsToMany<Plan, $this>
     */
    public function plans(): BelongsToMany
    {
        return $this->belongsToMany(Plan::class, 'plan_features')
            ->using(PlanFeature::class)
            ->withPivot(['value', 'is_unlimited'])
            ->withTimestamps();
    }

    /**
     * @return HasMany<TenantUsage, $this>
     */
    public function usages(): HasMany
    {
        return $this->hasMany(TenantUsage::class);
    }

    public function isBoolean(): bool
    {
        return $this->type === FeatureType::Boolean;
    }

    public function isLimit(): bool
    {
        return $this->type === FeatureType::Limit;
    }

    public function isResourceLimit(): bool
    {
        return $this->isLimit() && $this->enforcement === FeatureEnforcement::Resource;
    }

    public function isConsumptionLimit(): bool
    {
        return $this->isLimit() && ! $this->isResourceLimit();
    }

    protected static function booted(): void
    {
        static::creating(function (Feature $feature): void {
            if ($feature->isLimit() && $feature->enforcement === null) {
                $feature->enforcement = FeatureEnforcement::Consumption;
            }
        });
    }

    protected static function newFactory(): FeatureFactory
    {
        return FeatureFactory::new();
    }
}
