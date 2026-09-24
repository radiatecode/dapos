<?php

namespace DA\Admin\Models;

use DA\Admin\Database\Factories\SubscriptionItemFactory;
use DA\Admin\Enums\SubscriptionItemType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'subscription_id',
    'item_type',
    'reference_id',
    'quantity',
    'unit_price',
])]
class SubscriptionItem extends Model
{
    /** @use HasFactory<SubscriptionItemFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'quantity' => 1,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'item_type' => SubscriptionItemType::class,
            'quantity' => 'integer',
            'unit_price' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<Subscription, $this>
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function isPlan(): bool
    {
        return $this->item_type === SubscriptionItemType::Plan;
    }

    public function isAddon(): bool
    {
        return $this->item_type === SubscriptionItemType::Addon;
    }

    protected static function newFactory(): SubscriptionItemFactory
    {
        return SubscriptionItemFactory::new();
    }
}
