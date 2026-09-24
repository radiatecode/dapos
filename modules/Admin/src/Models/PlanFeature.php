<?php

namespace DA\Admin\Models;

use DA\Admin\Database\Factories\PlanFeatureFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

#[Fillable([
    'plan_id',
    'feature_id',
    'value',
    'is_unlimited',
])]
class PlanFeature extends Pivot
{
    /** @use HasFactory<PlanFeatureFactory> */
    use HasFactory;

    protected $table = 'plan_features';

    public $incrementing = true;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_unlimited' => false,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_unlimited' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Plan, $this>
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * @return BelongsTo<Feature, $this>
     */
    public function feature(): BelongsTo
    {
        return $this->belongsTo(Feature::class);
    }

    public function displayValue(): string
    {
        if ($this->is_unlimited) {
            return 'Unlimited';
        }

        if ($this->feature?->isBoolean()) {
            return $this->value === '1' ? 'Yes' : 'No';
        }

        return $this->value ?? '—';
    }

    protected static function newFactory(): PlanFeatureFactory
    {
        return PlanFeatureFactory::new();
    }
}
