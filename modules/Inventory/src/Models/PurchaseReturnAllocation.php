<?php

namespace DA\Inventory\Models;

use DA\Inventory\Database\Factories\PurchaseReturnAllocationFactory;
use DA\Inventory\Models\Queries\PurchaseReturnAllocationQueries;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

#[Fillable([
    'purchase_return_item_id',
    'inventory_layer_id',
    'product_variant_id',
    'quantity',
    'unit_cost',
    'total_cost',
])]
class PurchaseReturnAllocation extends Model
{
    public const UPDATED_AT = null;

    /** @use HasFactory<PurchaseReturnAllocationFactory> */
    use HasFactory;

    public static function queries(): PurchaseReturnAllocationQueries
    {
        return new PurchaseReturnAllocationQueries(static::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'unit_cost' => 'decimal:4',
            'total_cost' => 'decimal:4',
        ];
    }

    /**
     * @return BelongsTo<PurchaseReturnItem, $this>
     */
    public function purchaseReturnItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseReturnItem::class);
    }

    /**
     * @return BelongsTo<InventoryLayer, $this>
     */
    public function layer(): BelongsTo
    {
        return $this->belongsTo(InventoryLayer::class, 'inventory_layer_id');
    }

    /**
     * @return BelongsTo<ProductVariant, $this>
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    protected static function booted(): void
    {
        static::updating(function (): void {
            throw new RuntimeException('Purchase return allocations are immutable.');
        });

        static::deleting(function (): void {
            throw new RuntimeException('Purchase return allocations are immutable.');
        });
    }

    protected static function newFactory(): PurchaseReturnAllocationFactory
    {
        return PurchaseReturnAllocationFactory::new();
    }
}
