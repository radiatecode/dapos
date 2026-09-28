<?php

namespace DA\Inventory\Models;

use App\Models\Concerns\ScopedToCurrentTenant;
use DA\Inventory\Database\Factories\InventoryBalanceFactory;
use DA\Inventory\Models\Queries\InventoryBalanceQueries;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'store_id',
    'product_variant_id',
    'quantity',
    'reserved_quantity',
    'available_quantity',
    'stock_value',
])]
class InventoryBalance extends Model
{
    public const CREATED_AT = null;

    /** @use HasFactory<InventoryBalanceFactory> */
    use HasFactory, ScopedToCurrentTenant;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'quantity' => 0,
        'reserved_quantity' => 0,
        'available_quantity' => 0,
        'stock_value' => 0,
    ];

    public static function queries(): InventoryBalanceQueries
    {
        return new InventoryBalanceQueries(static::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'reserved_quantity' => 'decimal:4',
            'available_quantity' => 'decimal:4',
            'stock_value' => 'decimal:4',
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

    protected static function newFactory(): InventoryBalanceFactory
    {
        return InventoryBalanceFactory::new();
    }
}
