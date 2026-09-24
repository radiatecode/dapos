<?php

namespace DA\Inventory\Models;

use App\Models\Concerns\ScopedToCurrentTenant;
use DA\Inventory\Database\Factories\UnitFactory;
use DA\Inventory\Enums\UnitType;
use DA\Inventory\Models\Queries\UnitQueries;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'name',
    'short_name',
    'code',
    'unit_type',
    'precision',
    'is_active',
])]
class Unit extends Model
{
    /** @use HasFactory<UnitFactory> */
    use HasFactory, ScopedToCurrentTenant;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'precision' => 0,
        'is_active' => true,
    ];

    public static function queries(): UnitQueries
    {
        return new UnitQueries(static::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'unit_type' => UnitType::class,
            'precision' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    protected static function newFactory(): UnitFactory
    {
        return UnitFactory::new();
    }
}
