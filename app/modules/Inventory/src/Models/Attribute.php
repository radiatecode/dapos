<?php

namespace DA\Inventory\Models;

use App\Models\Concerns\ScopedToCurrentTenant;
use DA\Inventory\Database\Factories\AttributeFactory;
use DA\Inventory\Enums\AttributeInputType;
use DA\Inventory\Models\Queries\AttributeQueries;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'slug',
    'code',
    'input_type',
    'sort_order',
    'is_active',
])]
class Attribute extends Model
{
    /** @use HasFactory<AttributeFactory> */
    use HasFactory, ScopedToCurrentTenant;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'sort_order' => 0,
        'is_active' => true,
    ];

    public static function queries(): AttributeQueries
    {
        return new AttributeQueries(static::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'input_type' => AttributeInputType::class,
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<AttributeValue, $this>
     */
    public function values(): HasMany
    {
        return $this->hasMany(AttributeValue::class)
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    protected static function newFactory(): AttributeFactory
    {
        return AttributeFactory::new();
    }
}
