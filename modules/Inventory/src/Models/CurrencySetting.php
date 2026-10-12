<?php

namespace DA\Inventory\Models;

use App\Models\Concerns\ScopedToCurrentTenant;
use DA\Inventory\Database\Factories\CurrencySettingFactory;
use DA\Inventory\Enums\DecimalSeparator;
use DA\Inventory\Enums\SymbolPosition;
use DA\Inventory\Enums\ThousandsSeparator;
use DA\Inventory\Models\Queries\CurrencySettingQueries;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'currency_code',
    'currency_symbol',
    'decimal_places',
    'decimal_separator',
    'thousands_separator',
    'symbol_position',
    'show_decimals',
    'add_space',
])]
class CurrencySetting extends Model
{
    /** @use HasFactory<CurrencySettingFactory> */
    use HasFactory, ScopedToCurrentTenant;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'decimal_places' => 2,
        'decimal_separator' => 'dot',
        'thousands_separator' => 'comma',
        'symbol_position' => 'left',
        'show_decimals' => true,
        'add_space' => false,
    ];

    public static function queries(): CurrencySettingQueries
    {
        return new CurrencySettingQueries(static::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'decimal_places' => 'integer',
            'decimal_separator' => DecimalSeparator::class,
            'thousands_separator' => ThousandsSeparator::class,
            'symbol_position' => SymbolPosition::class,
            'show_decimals' => 'boolean',
            'add_space' => 'boolean',
        ];
    }

    protected static function newFactory(): CurrencySettingFactory
    {
        return CurrencySettingFactory::new();
    }
}
