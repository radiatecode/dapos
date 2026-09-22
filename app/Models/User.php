<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Models\Concerns\HasPermissions;
use App\Models\Concerns\ScopedToCurrentTenant;
use App\Models\Queries\UserQueries;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Users belong to at most one tenant via tenant_id. Queries are filtered by
 * the current TenantContext when it is set. Auth and platform operations
 * run without context and therefore see every user.
 */
#[Fillable(['name', 'email', 'password', 'photo'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasPermissions, Notifiable, ScopedToCurrentTenant;

    public static function queries(): UserQueries
    {
        return new UserQueries(static::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }
}
