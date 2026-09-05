<?php

namespace DA\Admin\Models;

use DA\Admin\Database\Factories\FeatureFactory;
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

    public function isBoolean(): bool
    {
        return $this->type === FeatureType::Boolean;
    }

    public function isLimit(): bool
    {
        return $this->type === FeatureType::Limit;
    }

    protected static function newFactory(): FeatureFactory
    {
        return FeatureFactory::new();
    }
}
