<?php

namespace Tests\Fixtures;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class TenantOwnedItem extends Model
{
    use BelongsToTenant;

    protected $table = 'tenant_owned_items';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'title',
    ];
}
