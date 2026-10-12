<?php

namespace DA\Inventory\Models;

use App\Models\Concerns\ScopedToCurrentTenant;
use DA\Inventory\Database\Factories\SaleItemFactory;
use DA\Inventory\Models\Queries\SaleItemQueries;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'sale_id',
    'product_variant_id',
    'product_variant_name',
    'quantity',
    'unit_price',
    'discount_amount',
    'tax_amount',
    'subtotal',
    'total',
    'cost_price',
    'cogs',
])]
class SaleItem extends Model
{
    /** @use HasFactory<SaleItemFactory> */
    use HasFactory, ScopedToCurrentTenant;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'tax_amount' => 0,
    ];

    public static function queries(): SaleItemQueries
    {
        return new SaleItemQueries(static::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'unit_price' => 'decimal:4',
            'discount_amount' => 'decimal:4',
            'tax_amount' => 'decimal:4',
            'subtotal' => 'decimal:4',
            'total' => 'decimal:4',
            'cost_price' => 'decimal:4',
            'cogs' => 'decimal:4',
        ];
    }

    /**
     * @return BelongsTo<Sale, $this>
     */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    /**
     * @return BelongsTo<ProductVariant, $this>
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    protected static function newFactory(): SaleItemFactory
    {
        return SaleItemFactory::new();
    }
}
