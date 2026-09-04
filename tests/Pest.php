<?php

use DA\Admin\Enums\AdminPermission;
use DA\Admin\Models\AdminPermission as AdminPermissionModel;
use DA\Admin\Models\AdminRole;
use DA\Admin\Models\AdminUser;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->in('Feature');

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/**
 * @param  list<AdminPermission|string>|null  $permissions
 */
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
