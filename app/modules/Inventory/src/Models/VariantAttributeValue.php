<?php

namespace DA\Inventory\Models;

use DA\Inventory\Database\Factories\VariantAttributeValueFactory;
use DA\Inventory\Models\Queries\VariantAttributeValueQueries;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'variant_id',
    'attribute_id',
    'attribute_value_id',
])]
class VariantAttributeValue extends Model
{
    /** @use HasFactory<VariantAttributeValueFactory> */
    use HasFactory;

    public static function queries(): VariantAttributeValueQueries
    {
        return new VariantAttributeValueQueries(static::class);
    }

    /**
     * @return BelongsTo<ProductVariant, $this>
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    /**
     * @return BelongsTo<Attribute, $this>
     */
    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class);
    }

    /**
     * @return BelongsTo<AttributeValue, $this>
     */
    public function attributeValue(): BelongsTo
    {
        return $this->belongsTo(AttributeValue::class);
    }

    protected static function newFactory(): VariantAttributeValueFactory
    {
        return VariantAttributeValueFactory::new();
    }
}
