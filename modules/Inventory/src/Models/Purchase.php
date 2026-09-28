<?php

namespace DA\Inventory\Models;

use App\Models\Concerns\ScopedToCurrentTenant;
use App\Models\User;
use DA\Inventory\Database\Factories\PurchaseFactory;
use DA\Inventory\Enums\PurchasePaymentStatus;
use DA\Inventory\Enums\PurchaseStatus;
use DA\Inventory\Models\Queries\PurchaseQueries;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'supplier_id',
    'store_id',
    'po_number',
    'order_date',
    'currency',
    'subtotal',
    'discount_amount',
    'tax_amount',
    'shipping_amount',
    'grand_total',
    'status',
    'payment_status',
    'notes',
])]
class Purchase extends Model
{
    /** @use HasFactory<PurchaseFactory> */
    use HasFactory, ScopedToCurrentTenant;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'discount_amount' => 0,
        'tax_amount' => 0,
        'shipping_amount' => 0,
        'status' => 'Pending',
        'payment_status' => 'Unpaid',
    ];

    public static function queries(): PurchaseQueries
    {
        return new PurchaseQueries(static::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'order_date' => 'date',
            'subtotal' => 'decimal:4',
            'discount_amount' => 'decimal:4',
            'tax_amount' => 'decimal:4',
            'shipping_amount' => 'decimal:4',
            'grand_total' => 'decimal:4',
            'status' => PurchaseStatus::class,
            'payment_status' => PurchasePaymentStatus::class,
        ];
    }

    /**
     * @return BelongsTo<Supplier, $this>
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * @return BelongsTo<Store, $this>
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * @return HasMany<PurchaseOrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class, 'purchase_order_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    protected static function newFactory(): PurchaseFactory
    {
        return PurchaseFactory::new();
    }
}
