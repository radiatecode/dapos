<?php

namespace DA\Inventory\Models;

use App\Models\Concerns\ScopedToCurrentTenant;
use DA\Inventory\Database\Factories\InventoryLayerFactory;
use DA\Inventory\Enums\InventoryLayerSourceType;
use DA\Inventory\Enums\InventoryLayerStatus;
use DA\Inventory\Models\Queries\InventoryLayerQueries;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'store_id',
    'product_variant_id',
    'supplier_id',
    'source_type',
    'source_id',
    'source_item_id',
    'received_qty',
    'remaining_qty',
    'unit_cost',
    'total_cost',
    'received_at',
    'batch_id',
    'expiry_date',
    'status',
])]
class InventoryLayer extends Model
{
    /** @use HasFactory<InventoryLayerFactory> */
    use HasFactory, ScopedToCurrentTenant;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'Open',
    ];

    public static function queries(): InventoryLayerQueries
    {
        return new InventoryLayerQueries(static::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'source_type' => InventoryLayerSourceType::class,
            'received_qty' => 'decimal:4',
            'remaining_qty' => 'decimal:4',
            'unit_cost' => 'decimal:4',
            'total_cost' => 'decimal:4',
            'received_at' => 'datetime',
            'expiry_date' => 'date',
            'status' => InventoryLayerStatus::class,
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
     * @return BelongsTo<Supplier, $this>
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * @return HasMany<InventoryAllocation, $this>
     */
    public function allocations(): HasMany
    {
        return $this->hasMany(InventoryAllocation::class);
    }

    /**
     * @return HasMany<StockMovement, $this>
     */
    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    protected static function newFactory(): InventoryLayerFactory
    {
        return InventoryLayerFactory::new();
    }
}
