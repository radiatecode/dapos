<?php

namespace DA\Inventory\Models;

use App\Concerns\BelongsToOwner;
use App\Models\Concerns\ScopedToCurrentTenant;
use DA\Inventory\Database\Factories\CustomerFactory;
use DA\Inventory\Models\Queries\CustomerQueries;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'name',
    'phone',
    'email',
    'address',
    'created_by',
    'updated_by',
])]
class Customer extends Model
{
    /** @use HasFactory<CustomerFactory> */
    use BelongsToOwner, HasFactory, ScopedToCurrentTenant;

    public static function queries(): CustomerQueries
    {
        return new CustomerQueries(static::class);
    }

    protected static function newFactory(): CustomerFactory
    {
        return CustomerFactory::new();
    }
}
