<?php

namespace DA\Admin\Models;

use DA\Admin\Database\Factories\TenantUsageFactory;
use DA\Admin\Models\Queries\TenantUsageQueries;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'tenant_id',
    'feature_id',
    'usage',
    'period_start',
    'period_end',
])]
class TenantUsage extends Model
{
    /** @use HasFactory<TenantUsageFactory> */
    use HasFactory;

    public const CREATED_AT = null;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'usage' => 0,
    ];

    public static function queries(): TenantUsageQueries
    {
        return new TenantUsageQueries(static::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'usage' => 'integer',
            'period_start' => 'datetime',
            'period_end' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * @return BelongsTo<Feature, $this>
     */
    public function feature(): BelongsTo
    {
        return $this->belongsTo(Feature::class);
    }

    protected static function newFactory(): TenantUsageFactory
    {
        return TenantUsageFactory::new();
    }
}
