<?php

use App\Enums\Permission;
use App\Models\User;
use DA\Admin\Enums\AdminPermission;
use DA\Admin\Enums\BillingInterval;
use DA\Admin\Enums\CouponDiscountType;
use DA\Admin\Enums\FeatureType;
use DA\Admin\Enums\PaymentMethod;
use DA\Admin\Models\AdminPermission as AdminPermissionModel;
use DA\Admin\Models\AdminRole;
use DA\Admin\Models\AdminUser;
use DA\Admin\Models\Currency;
use DA\Admin\Models\Plan;
use DA\Admin\Models\Tenant;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->in('Feature');

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/**
 * @param  list<AdminPermission|string>|null  $permissions
 */
/**
 * @param  list<Permission|string>|null  $permissions
 */
function actingAsTenantUser(?Tenant $tenant = null, ?array $permissions = null): User
{
    $tenant ??= Tenant::factory()->create();

    $user = User::factory()
        ->forTenant($tenant)
        ->withPermissions($permissions)
        ->create();

    Sanctum::actingAs($user);

    return $user->fresh(['roles']) ?? $user;
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function tenantUserPayload(array $overrides = []): array
{
    return array_replace_recursive([
        'name' => 'Cashier One',
        'email' => 'cashier@shop.test',
        'password' => 'password',
        'password_confirmation' => 'password',
    ], $overrides);
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function rolePayload(array $overrides = []): array
{
    return array_replace_recursive([
        'name' => 'Cashier',
        'slug' => 'cashier',
        'permissions' => [Permission::SalesView->value],
    ], $overrides);
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function categoryPayload(array $overrides = []): array
{
    return array_replace_recursive([
        'name' => 'Beverages',
        'slug' => 'beverages',
        'description' => 'Drinks and juices',
        'sort_order' => 0,
        'is_active' => true,
    ], $overrides);
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function brandPayload(array $overrides = []): array
{
    return array_replace_recursive([
        'name' => 'Acme',
        'slug' => 'acme',
        'description' => 'House brand',
        'is_active' => true,
    ], $overrides);
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function unitPayload(array $overrides = []): array
{
    return array_replace_recursive([
        'name' => 'Kilogram',
        'short_name' => 'kg',
        'code' => 'KG',
        'unit_type' => 'weight',
        'precision' => 3,
        'is_active' => true,
    ], $overrides);
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function attributePayload(array $overrides = []): array
{
    return array_replace_recursive([
        'name' => 'Color',
        'slug' => 'color',
        'input_type' => 'select',
        'sort_order' => 0,
        'is_active' => true,
    ], $overrides);
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function attributeValuePayload(array $overrides = []): array
{
    return array_replace_recursive([
        'value' => 'Red',
        'slug' => 'red',
        'sort_order' => 0,
        'is_active' => true,
    ], $overrides);
}

function actingAsPlatformAdmin(?array $permissions = null, string $guard = 'admin'): AdminUser
{
    $admin = AdminUser::factory()->create();

    grantAdminPermissions($admin, $permissions ?? AdminPermission::cases());

    test()->actingAs($admin, $guard);

    return $admin;
}

/**
 * @param  list<AdminPermission|string>  $permissions
 */
function grantAdminPermissions(AdminUser $admin, array $permissions): void
{
    $permissionIds = collect($permissions)->map(function (AdminPermission|string $permission): int {
        $enum = $permission instanceof AdminPermission ? $permission : AdminPermission::from($permission);

        return AdminPermissionModel::query()->firstOrCreate(
            ['key' => $enum->value],
            [
                'name' => $enum->label(),
                'group' => $enum->group(),
            ],
        )->id;
    });

    $role = AdminRole::factory()->create();
    $role->permissions()->sync($permissionIds);
    $admin->adminRoles()->sync([$role->id]);
}

function seedAdminPermissionCatalog(): void
{
    foreach (AdminPermission::cases() as $permission) {
        AdminPermissionModel::query()->firstOrCreate(
            ['key' => $permission->value],
            [
                'name' => $permission->label(),
                'group' => $permission->group(),
            ],
        );
    }
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function planPayload(array $overrides = []): array
{
    $defaults = [
        'name' => 'Starter',
        'code' => 'starter',
        'description' => 'For small teams',
        'billing_interval' => BillingInterval::Monthly->value,
        'price' => '19.00',
        'trial_days' => 14,
        'is_active' => '1',
        'features' => [],
    ];

    if (! array_key_exists('currency_id', $overrides)) {
        $defaults['currency_id'] = Currency::factory()->create()->id;
    }

    return array_replace_recursive($defaults, $overrides);
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function featurePayload(array $overrides = []): array
{
    return array_replace_recursive([
        'name' => 'Users',
        'code' => 'users',
        'type' => FeatureType::Limit->value,
        'description' => 'Maximum users on the tenant.',
    ], $overrides);
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function addonPayload(array $overrides = []): array
{
    $defaults = [
        'name' => 'Extra location',
        'code' => 'extra-location',
        'description' => 'Add one more store location.',
        'billing_interval' => BillingInterval::Monthly->value,
        'price' => '9.00',
        'is_active' => '1',
    ];

    if (! array_key_exists('currency_id', $overrides)) {
        $defaults['currency_id'] = Currency::factory()->create()->id;
    }

    return array_replace_recursive($defaults, $overrides);
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function subscriptionPayload(array $overrides = []): array
{
    $defaults = [
        'start_trial' => '1',
        'addon_ids' => [],
        'grace_days' => '7',
    ];

    if (! array_key_exists('tenant_id', $overrides)) {
        $defaults['tenant_id'] = Tenant::factory()->create()->id;
    }

    if (! array_key_exists('plan_id', $overrides)) {
        $defaults['plan_id'] = Plan::factory()->create(['trial_days' => 14])->id;
    }

    return array_replace_recursive($defaults, $overrides);
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function couponPayload(array $overrides = []): array
{
    return array_replace_recursive([
        'name' => 'Launch discount',
        'code' => 'SAVE10',
        'description' => 'Ten percent off',
        'discount_type' => CouponDiscountType::Percentage->value,
        'discount_value' => '10.00',
        'max_redemptions_per_tenant' => '1',
        'is_active' => '1',
    ], $overrides);
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function recordPaymentPayload(array $overrides = []): array
{
    return array_replace_recursive([
        'payment_method' => PaymentMethod::Manual->value,
        'transaction_id' => null,
    ], $overrides);
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function tenantPayload(array $overrides = []): array
{
    return array_replace_recursive([
        'name' => 'Acme Coffee',
        'legal_name' => 'Acme Coffee Ltd',
        'timezone' => 'Asia/Dhaka',
        'currency' => 'BDT',
        'address' => [
            'line_1' => '12 Baker Street',
            'line_2' => 'Suite 4',
            'city' => 'Dhaka',
            'state' => 'Dhaka',
            'postal_code' => '1205',
            'country' => 'BD',
        ],
        'contact' => [
            'name' => 'Jane Doe',
            'email' => 'jane@acme.test',
            'phone' => '+8801700000000',
            'website' => 'https://acme.test',
        ],
        'billing' => [
            'name' => 'Acme Billing',
            'email' => 'billing@acme.test',
            'phone' => '+8801700000001',
            'tax_id' => 'TAX-123',
            'address' => [
                'line_1' => '99 Billing Road',
                'line_2' => null,
                'city' => 'Dhaka',
                'state' => 'Dhaka',
                'postal_code' => '1212',
                'country' => 'BD',
            ],
        ],
    ], $overrides);
}
