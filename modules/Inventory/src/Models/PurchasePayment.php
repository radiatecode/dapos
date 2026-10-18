<?php

namespace DA\Inventory\Models;

use App\Models\Concerns\ScopedToCurrentTenant;
use App\Models\User;
use DA\Inventory\Database\Factories\PurchasePaymentFactory;
use DA\Inventory\Models\Queries\PurchasePaymentQueries;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'purchase_id',
    'amount',
    'reference_number',
    'note',
    'paid_at',
    'recorded_by',
])]
class PurchasePayment extends Model
{
    /** @use HasFactory<PurchasePaymentFactory> */
    use HasFactory, ScopedToCurrentTenant;

    public static function queries(): PurchasePaymentQueries
    {
        return new PurchasePaymentQueries(static::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:4',
            'paid_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Purchase, $this>
     */
    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    protected static function newFactory(): PurchasePaymentFactory
    {
        return PurchasePaymentFactory::new();
    }
}
