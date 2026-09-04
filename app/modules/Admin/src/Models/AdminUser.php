<?php

namespace DA\Admin\Models;

use DA\Admin\Database\Factories\AdminUserFactory;
use DA\Admin\Models\Concerns\HasAdminAuthorization;
use DA\Admin\Models\Queries\AdminUserQueries;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class AdminUser extends Authenticatable
{
    /** @use HasFactory<AdminUserFactory> */
    use HasAdminAuthorization, HasFactory, Notifiable;

    public static function queries(): AdminUserQueries
    {
        return new AdminUserQueries(static::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    protected static function newFactory(): AdminUserFactory
    {
        return AdminUserFactory::new();
    }
}
