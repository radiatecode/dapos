<?php

namespace DA\Inventory\Models;

use App\Models\Concerns\ScopedToCurrentTenant;
use App\Models\User;
use DA\Inventory\Database\Factories\StockAdjustmentFactory;
use DA\Inventory\Enums\StockAdjustmentStatus;
use DA\Inventory\Enums\StockAdjustmentType;
use DA\Inventory\Models\Queries\StockAdjustmentQueries;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'store_id',
    'product_variant_id',
    'reference_number',
    'adjustment_date',
    'adjustment_type',
    'reason',
    'status',
    'notes',
    'system_quantity',
    'adjusted_quantity',
    'difference_quantity',
    'unit_cost',
    'total_cost',
])]
class StockAdjustment extends Model
{
    /** @use HasFactory<StockAdjustmentFactory> */
    use HasFactory, ScopedToCurrentTenant;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'Draft',
    ];

    public static function queries(): StockAdjustmentQueries
    {
        return new StockAdjustmentQueries(static::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'adjustment_date' => 'date',
            'adjustment_type' => StockAdjustmentType::class,
            'status' => StockAdjustmentStatus::class,
            'system_quantity' => 'decimal:4',
            'adjusted_quantity' => 'decimal:4',
            'difference_quantity' => 'decimal:4',
            'unit_cost' => 'decimal:4',
            'total_cost' => 'decimal:4',
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

    protected static function newFactory(): StockAdjustmentFactory
    {
        return StockAdjustmentFactory::new();
    }
}
