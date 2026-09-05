<?php

namespace DA\Admin\Models;

use DA\Admin\Database\Factories\TenantFactory;
use DA\Admin\Enums\TenantStatus;
use DA\Admin\Models\Queries\TenantQueries;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'name',
    'slug',
    'legal_name',
    'logo',
    'status',
    'timezone',
    'currency',
    'address_line_1',
    'address_line_2',
    'city',
    'state',
    'postal_code',
    'country',
    'contact_person_name',
    'contact_person_email',
    'contact_person_phone',
    'website',
    'billing_name',
    'billing_email',
    'billing_phone',
    'billing_address_line_1',
    'billing_address_line_2',
    'billing_city',
    'billing_state',
    'billing_postal_code',
    'billing_country',
    'tax_id',
])]
class Tenant extends Model
{
    /** @use HasFactory<TenantFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => TenantStatus::Active->value,
        'timezone' => 'Asia/Dhaka',
        'currency' => 'BDT',
    ];

    public static function queries(): TenantQueries
    {
        return new TenantQueries(static::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => TenantStatus::class,
        ];
    }

    /**
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function isActive(): bool
    {
        return $this->status === TenantStatus::Active;
    }

    public function isSuspended(): bool
    {
        return $this->status === TenantStatus::Suspended;
    }

    protected static function newFactory(): TenantFactory
    {
        return TenantFactory::new();
    }
}
