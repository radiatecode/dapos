<?php

namespace DA\Inventory\Models;

use App\Concerns\BelongsToOwner;
use App\Models\Concerns\ScopedToCurrentTenant;
use DA\Inventory\Concerns\OwnershipActionAuthorize;
use DA\Inventory\Models\Queries\TaxSettingQueries;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaxSetting extends Model
{
    use BelongsToOwner;
    use HasFactory;
    use OwnershipActionAuthorize;
    use ScopedToCurrentTenant;

    protected $fillable = [
        'tax_mode',
        'tax_calculation_base',
    ];

    public static function queries(): TaxSettingQueries
    {
        return new TaxSettingQueries(static::class);
    }
}
