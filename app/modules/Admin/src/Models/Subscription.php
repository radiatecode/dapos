<?php

namespace DA\Admin\Models;

use DA\Admin\Database\Factories\SubscriptionFactory;
use DA\Admin\Enums\SubscriptionStatus;
use DA\Admin\Models\Queries\SubscriptionQueries;
use DA\Admin\Services\FeatureService;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'tenant_id',
    'plan_id',
    'status',
    'starts_at',
    'trial_ends_at',
    'current_period_start',
    'current_period_end',
    'cancel_at_period_end',
    'grace_days',
    'grace_ends_at',
    'grace_notified_at',
    'cancelled_at',
    'ended_at',
    'paused_at',
    'paused_from_status',
])]
class Subscription extends Model
{
    public const DEFAULT_GRACE_DAYS = 7;

    /** @use HasFactory<SubscriptionFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'cancel_at_period_end' => false,
        'grace_days' => self::DEFAULT_GRACE_DAYS,
    ];

    public static function queries(): SubscriptionQueries
    {
        return new SubscriptionQueries(static::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => SubscriptionStatus::class,
            'starts_at' => 'datetime',
            'trial_ends_at' => 'datetime',
            'current_period_start' => 'datetime',
            'current_period_end' => 'datetime',
            'cancel_at_period_end' => 'boolean',
            'grace_days' => 'integer',
            'grace_ends_at' => 'datetime',
            'grace_notified_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'ended_at' => 'datetime',
            'paused_at' => 'datetime',
            'paused_from_status' => SubscriptionStatus::class,
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
     * @return BelongsTo<Plan, $this>
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * @return HasMany<SubscriptionItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(SubscriptionItem::class);
    }

    /**
     * @return HasMany<SubscriptionEvent, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(SubscriptionEvent::class)->orderByDesc('occurred_at')->orderByDesc('id');
    }

    /**
     * @return HasMany<Invoice, $this>
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class)->orderByDesc('id');
    }

    /**
     * @param  Builder<Subscription>  $query
     * @return Builder<Subscription>
     */
    #[Scope]
    protected function current(Builder $query): Builder
    {
        return $query->whereIn('status', [
            SubscriptionStatus::Trialing->value,
            SubscriptionStatus::Active->value,
            SubscriptionStatus::PastDue->value,
            SubscriptionStatus::Paused->value,
        ]);
    }

    public function isCurrent(): bool
    {
        return $this->status instanceof SubscriptionStatus && $this->status->isCurrent();
    }

    public function isEnded(): bool
    {
        return $this->status instanceof SubscriptionStatus && $this->status->isEnded();
    }

    public function isInGrace(): bool
    {
        return $this->status === SubscriptionStatus::PastDue
            && $this->grace_ends_at !== null
            && $this->grace_ends_at->isFuture();
    }

    public function hasFeature(string $code): bool
    {
        return $this->entitlements()->hasFeature($this, $code);
    }

    public function can(string $code): bool
    {
        return $this->entitlements()->can($this, $code);
    }

    public function limit(string $code): ?int
    {
        return $this->entitlements()->limit($this, $code);
    }

    public function isUnlimited(string $code): bool
    {
        return $this->entitlements()->isUnlimited($this, $code);
    }

    public function canConsume(string $code, int $amount = 1): bool
    {
        return $this->entitlements()->canConsume($this, $code, $amount);
    }

    public function consume(string $code, int $amount = 1): void
    {
        $this->entitlements()->consume($this, $code, $amount);
    }

    public function release(string $code, int $amount = 1): void
    {
        $this->entitlements()->release($this, $code, $amount);
    }

    private function entitlements(): FeatureService
    {
        return app(FeatureService::class);
    }

    protected static function newFactory(): SubscriptionFactory
    {
        return SubscriptionFactory::new();
    }
}
