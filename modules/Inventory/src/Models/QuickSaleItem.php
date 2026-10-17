<?php

namespace DA\Inventory\Models;

use App\Models\Concerns\ScopedToCurrentTenant;
use DA\Inventory\Database\Factories\QuickSaleItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'quick_sale_id',
    'title',
    'unit_amount',
    'quantity',
    'amount',
    'sort_order',
])]
class QuickSaleItem extends Model
{
    /** @use HasFactory<QuickSaleItemFactory> */
    use HasFactory, ScopedToCurrentTenant;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'quantity' => 1,
        'sort_order' => 0,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'unit_amount' => 'decimal:4',
            'quantity' => 'decimal:4',
            'amount' => 'decimal:4',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<QuickSale, $this>
     */
    public function quickSale(): BelongsTo
    {
        return $this->belongsTo(QuickSale::class);
    }

    protected static function newFactory(): QuickSaleItemFactory
    {
        return QuickSaleItemFactory::new();
    }
}
