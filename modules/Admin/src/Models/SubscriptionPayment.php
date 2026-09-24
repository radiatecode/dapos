<?php

namespace DA\Admin\Models;

use DA\Admin\Database\Factories\SubscriptionPaymentFactory;
use DA\Admin\Enums\PaymentMethod;
use DA\Admin\Enums\PaymentStatus;
use DA\Admin\Models\Queries\SubscriptionPaymentQueries;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'tenant_id',
    'invoice_id',
    'amount',
    'currency_id',
    'payment_method',
    'transaction_id',
    'status',
    'paid_at',
])]
class SubscriptionPayment extends Model
{
    /** @use HasFactory<SubscriptionPaymentFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => PaymentStatus::Pending->value,
        'payment_method' => PaymentMethod::Manual->value,
    ];

    public static function queries(): SubscriptionPaymentQueries
    {
        return new SubscriptionPaymentQueries(static::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'payment_method' => PaymentMethod::class,
            'status' => PaymentStatus::class,
            'paid_at' => 'datetime',
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
     * @return BelongsTo<Invoice, $this>
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * @return BelongsTo<Currency, $this>
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function isSucceeded(): bool
    {
        return $this->status === PaymentStatus::Succeeded;
    }

    protected static function newFactory(): SubscriptionPaymentFactory
    {
        return SubscriptionPaymentFactory::new();
    }
}
