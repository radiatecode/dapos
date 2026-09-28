<?php

namespace DA\Inventory\Models;

use App\Models\Concerns\ScopedToCurrentTenant;
use DA\Inventory\Database\Factories\InventoryAllocationFactory;
use DA\Inventory\Enums\AllocationSourceType;
use DA\Inventory\Models\Queries\InventoryAllocationQueries;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

#[Fillable([
    'store_id',
    'product_variant_id',
    'inventory_layer_id',
    'source_type',
    'source_id',
    'source_item_id',
    'quantity',
    'unit_cost',
    'total_cost',
])]
class InventoryAllocation extends Model
{
    public const UPDATED_AT = null;

    /** @use HasFactory<InventoryAllocationFactory> */
    use HasFactory, ScopedToCurrentTenant;

    public static function queries(): InventoryAllocationQueries
    {
        return new InventoryAllocationQueries(static::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'source_type' => AllocationSourceType::class,
            'quantity' => 'decimal:4',
            'unit_cost' => 'decimal:4',
            'total_cost' => 'decimal:4',
        ];
    }

    /**
     * @return BelongsTo<InventoryLayer, $this>
     */
    public function layer(): BelongsTo
    {
        return $this->belongsTo(InventoryLayer::class, 'inventory_layer_id');
    }

    /**
     * @return BelongsTo<Store, $this>
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
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
            throw new RuntimeException('Inventory allocations are immutable.');
        });

        static::deleting(function (): void {
            throw new RuntimeException('Inventory allocations are immutable.');
        });
    }

    protected static function newFactory(): InventoryAllocationFactory
    {
        return InventoryAllocationFactory::new();
    }
}
