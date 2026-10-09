<?php

namespace DA\Inventory\Models;

use App\Concerns\BelongsToOwner;
use App\Models\Concerns\ScopedToCurrentTenant;
use DA\Inventory\Database\Factories\TaxFactory;
use DA\Inventory\Models\Queries\TaxQueries;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tax extends Model
{
    /** @use HasFactory<TaxFactory> */
    use BelongsToOwner, HasFactory, ScopedToCurrentTenant;

    protected $fillable = [
        'tax_name',
        'tax_type',
        'tax_rate',
        'is_active',
    ];

    public static function queries(): TaxQueries
    {
        return new TaxQueries(static::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tax_type' => 'integer',
            'tax_rate' => 'float',
            'is_active' => 'boolean',
        ];
    }

    protected static function newFactory(): TaxFactory
    {
        return TaxFactory::new();
    }
}
