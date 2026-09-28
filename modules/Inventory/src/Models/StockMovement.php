<?php

namespace DA\Inventory\Models;

use App\Models\Concerns\ScopedToCurrentTenant;
use App\Models\User;
use DA\Inventory\Database\Factories\StockMovementFactory;
use DA\Inventory\Enums\StockDirection;
use DA\Inventory\Enums\StockMovementType;
use DA\Inventory\Enums\StockReferenceType;
use DA\Inventory\Models\Queries\StockMovementQueries;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

#[Fillable([
    'store_id',
    'product_variant_id',
    'movement_type',
    'quantity',
    'unit_cost',
    'reference_type',
    'reference_id',
    'reference_item_id',
    'inventory_layer_id',
    'batch_id',
    'serial_id',
    'direction',
    'occurred_at',
    'created_by',
])]
class StockMovement extends Model
{
    public const UPDATED_AT = null;

    /** @use HasFactory<StockMovementFactory> */
    use HasFactory, ScopedToCurrentTenant;

    public static function queries(): StockMovementQueries
    {
        return new StockMovementQueries(static::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'movement_type' => StockMovementType::class,
            'quantity' => 'decimal:4',
            'unit_cost' => 'decimal:4',
            'reference_type' => StockReferenceType::class,
            'direction' => StockDirection::class,
            'occurred_at' => 'datetime',
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
     * @return BelongsTo<ProductVariant, $this>
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    /**
     * @return BelongsTo<InventoryLayer, $this>
     */
    public function layer(): BelongsTo
    {
        return $this->belongsTo(InventoryLayer::class, 'inventory_layer_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    protected static function booted(): void
    {
        static::updating(function (): void {
            throw new RuntimeException('Stock movements are immutable.');
        });

        static::deleting(function (): void {
            throw new RuntimeException('Stock movements are immutable.');
        });
    }

    protected static function newFactory(): StockMovementFactory
    {
        return StockMovementFactory::new();
    }
}
