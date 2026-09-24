<?php

namespace DA\Admin\Models;

use DA\Admin\Database\Factories\SubscriptionEventFactory;
use DA\Admin\Enums\SubscriptionEventType;
use DA\Admin\Enums\SubscriptionStatus;
use DA\Admin\Models\Queries\SubscriptionEventQueries;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'tenant_id',
    'subscription_id',
    'event_type',
    'old_status',
    'new_status',
    'metadata',
    'occurred_at',
])]
class SubscriptionEvent extends Model
{
    /** @use HasFactory<SubscriptionEventFactory> */
    use HasFactory;

    public $timestamps = false;

    public static function queries(): SubscriptionEventQueries
    {
        return new SubscriptionEventQueries(static::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'event_type' => SubscriptionEventType::class,
            'old_status' => SubscriptionStatus::class,
            'new_status' => SubscriptionStatus::class,
            'metadata' => 'array',
            'occurred_at' => 'datetime',
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
     * @return BelongsTo<Subscription, $this>
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    protected static function newFactory(): SubscriptionEventFactory
    {
        return SubscriptionEventFactory::new();
    }
}
