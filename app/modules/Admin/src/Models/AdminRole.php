<?php

namespace DA\Admin\Models;

use DA\Admin\Database\Factories\AdminRoleFactory;
use DA\Admin\Models\Queries\AdminRoleQueries;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['name', 'slug', 'description'])]
class AdminRole extends Model
{
    /** @use HasFactory<AdminRoleFactory> */
    use HasFactory;

    public static function queries(): AdminRoleQueries
    {
        return new AdminRoleQueries(static::class);
    }

    /**
     * @return BelongsToMany<AdminPermission, $this>
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(AdminPermission::class, 'admin_permission_role');
    }

    /**
     * @return BelongsToMany<AdminUser, $this>
     */
    public function adminUsers(): BelongsToMany
    {
        return $this->belongsToMany(AdminUser::class, 'admin_role_user');
    }

    protected static function newFactory(): AdminRoleFactory
    {
        return AdminRoleFactory::new();
    }
}
