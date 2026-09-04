<?php

namespace DA\Admin\Models;

use DA\Admin\Database\Factories\AdminPermissionFactory;
use DA\Admin\Models\Queries\AdminPermissionQueries;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['name', 'key', 'group'])]
class AdminPermission extends Model
{
    /** @use HasFactory<AdminPermissionFactory> */
    use HasFactory;

    public static function queries(): AdminPermissionQueries
    {
        return new AdminPermissionQueries(static::class);
    }

    /**
     * @return BelongsToMany<AdminRole, $this>
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(AdminRole::class, 'admin_permission_role');
    }

    protected static function newFactory(): AdminPermissionFactory
    {
        return AdminPermissionFactory::new();
    }
}
