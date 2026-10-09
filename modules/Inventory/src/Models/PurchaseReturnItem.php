<?php

namespace DA\Inventory\Models;

use DA\Inventory\Database\Factories\PurchaseReturnItemFactory;
use DA\Inventory\Models\Queries\PurchaseReturnItemQueries;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'purchase_return_id',
    'purchase_order_item_id',
    'product_variant_id',
    'quantity',
    'unit_cost',
    'tax_amount',
    'discount_amount',
    'subtotal',
    'total_amount',
])]
class PurchaseReturnItem extends Model
{
    /** @use HasFactory<PurchaseReturnItemFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'tax_amount' => 0,
        'discount_amount' => 0,
    ];

    public static function queries(): PurchaseReturnItemQueries
    {
        return new PurchaseReturnItemQueries(static::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'unit_cost' => 'decimal:4',
            'tax_amount' => 'decimal:4',
            'discount_amount' => 'decimal:4',
            'subtotal' => 'decimal:4',
            'total_amount' => 'decimal:4',
        ];
    }

    /**
     * @return BelongsTo<PurchaseReturn, $this>
     */
    public function purchaseReturn(): BelongsTo
    {
        return $this->belongsTo(PurchaseReturn::class);
    }

    /**
     * @return BelongsTo<PurchaseOrderItem, $this>
     */
    public function purchaseOrderItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderItem::class);
    }

    /**
     * @return BelongsTo<ProductVariant, $this>
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    /**
     * @return HasMany<PurchaseReturnAllocation, $this>
     */
    public function allocations(): HasMany
    {
        return $this->hasMany(PurchaseReturnAllocation::class);
    }

    protected static function newFactory(): PurchaseReturnItemFactory
    {
        return PurchaseReturnItemFactory::new();
    }
}
