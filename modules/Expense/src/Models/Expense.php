<?php

namespace DA\Expense\Models;

use App\Models\Concerns\RestrictsToAllocatedStore;
use App\Models\Concerns\ScopedToCurrentTenant;
use App\Models\User;
use DA\Expense\Database\Factories\ExpenseFactory;
use DA\Expense\Enums\ExpenseStatus;
use DA\Expense\Models\Queries\ExpenseQueries;
use DA\Inventory\Concerns\OwnershipActionAuthorize;
use DA\Inventory\Models\Store;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'category_id',
    'store_id',
    'description',
    'amount',
    'expense_date',
    'attachment',
])]
class Expense extends Model
{
    /** @use HasFactory<ExpenseFactory> */
    use HasFactory, OwnershipActionAuthorize, RestrictsToAllocatedStore, ScopedToCurrentTenant;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'pending',
    ];

    public static function queries(): ExpenseQueries
    {
        return new ExpenseQueries(static::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:4',
            'expense_date' => 'date',
            'status' => ExpenseStatus::class,
            'approval_time' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<ExpenseCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'category_id');
    }

    /**
     * @return BelongsTo<Store, $this>
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id');
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

    protected static function newFactory(): ExpenseFactory
    {
        return ExpenseFactory::new();
    }
}
