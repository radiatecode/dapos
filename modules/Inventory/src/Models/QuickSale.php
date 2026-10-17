<?php

namespace DA\Inventory\Models;

use App\Models\Concerns\ScopedToCurrentTenant;
use App\Models\User;
use DA\Inventory\Database\Factories\QuickSaleFactory;
use DA\Inventory\Enums\PaymentMethod;
use DA\Inventory\Models\Queries\QuickSaleQueries;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'store_id',
    'invoice_number',
    'sale_date',
    'total_items',
    'grand_total',
    'payment_method',
    'received_amount',
    'change_amount',
    'reference_number',
    'note',
    'cashier_id',
])]
class QuickSale extends Model
{
    /** @use HasFactory<QuickSaleFactory> */
    use HasFactory, ScopedToCurrentTenant;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'change_amount' => 0,
    ];

    public static function queries(): QuickSaleQueries
    {
        return new QuickSaleQueries(static::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sale_date' => 'date',
            'total_items' => 'integer',
            'grand_total' => 'decimal:4',
            'payment_method' => PaymentMethod::class,
            'received_amount' => 'decimal:4',
            'change_amount' => 'decimal:4',
        ];
    }

    /**
     * @return BelongsTo<Store, $this>
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    /**
     * @return HasMany<QuickSaleItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(QuickSaleItem::class)->orderBy('sort_order');
    }

    protected static function newFactory(): QuickSaleFactory
    {
        return QuickSaleFactory::new();
    }
}
