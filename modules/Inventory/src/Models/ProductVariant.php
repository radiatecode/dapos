<?php

namespace DA\Inventory\Models;

use App\Models\Concerns\ScopedToCurrentTenant;
use DA\Inventory\Database\Factories\ProductVariantFactory;
use DA\Inventory\Enums\BarcodeType;
use DA\Inventory\Models\Queries\ProductVariantQueries;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'product_id',
    'sku',
    'barcode',
    'barcode_type',
    'name',
    'cost_price',
    'selling_price',
    'compare_at_price',
    'weight',
    'quantity',
    'min_stock_level',
    'image',
    'is_default',
    'is_active',
])]
class ProductVariant extends Model
{
    /** @use HasFactory<ProductVariantFactory> */
    use HasFactory, ScopedToCurrentTenant, SoftDeletes;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_default' => false,
        'is_active' => true,
    ];

    public static function queries(): ProductVariantQueries
    {
        return new ProductVariantQueries(static::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'barcode_type' => BarcodeType::class,
            'cost_price' => 'decimal:4',
            'selling_price' => 'decimal:4',
            'compare_at_price' => 'decimal:4',
            'weight' => 'decimal:4',
            'quantity' => 'decimal:4',
            'min_stock_level' => 'integer',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return HasMany<VariantAttributeValue, $this>
     */
    public function attributeValues(): HasMany
    {
        return $this->hasMany(VariantAttributeValue::class, 'variant_id');
    }

    protected static function newFactory(): ProductVariantFactory
    {
        return ProductVariantFactory::new();
    }
}
