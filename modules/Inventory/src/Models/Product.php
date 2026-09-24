<?php

namespace DA\Inventory\Models;

use App\Models\Concerns\ScopedToCurrentTenant;
use DA\Inventory\Database\Factories\ProductFactory;
use DA\Inventory\Enums\ProductType;
use DA\Inventory\Models\Queries\ProductQueries;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'category_id',
    'brand_id',
    'unit_id',
    'name',
    'slug',
    'description',
    'image',
    'product_type',
    'track_inventory',
    'is_active',
    'is_stock_out',
    'manufacture_date',
    'expire_date',
    'warranty_in_days',
    'guarantee_in_days',
])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory, ScopedToCurrentTenant, SoftDeletes;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'track_inventory' => true,
        'is_active' => true,
        'is_stock_out' => false,
    ];

    public static function queries(): ProductQueries
    {
        return new ProductQueries(static::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'product_type' => ProductType::class,
            'track_inventory' => 'boolean',
            'is_active' => 'boolean',
            'is_stock_out' => 'boolean',
            'manufacture_date' => 'date',
            'expire_date' => 'date',
            'warranty_in_days' => 'integer',
            'guarantee_in_days' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return BelongsTo<Brand, $this>
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * @return BelongsTo<Unit, $this>
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * @return HasMany<ProductAttribute, $this>
     */
    public function productAttributes(): HasMany
    {
        return $this->hasMany(ProductAttribute::class)
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    /**
     * @return HasMany<ProductVariant, $this>
     */
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)
            ->orderByDesc('is_default')
            ->orderBy('id');
    }

    /**
     * @return HasOne<ProductVariant, $this>
     */
    public function defaultVariant(): HasOne
    {
        return $this->hasOne(ProductVariant::class)->where('is_default', true);
    }

    protected static function newFactory(): ProductFactory
    {
        return ProductFactory::new();
    }
}
