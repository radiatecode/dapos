<?php

use App\Enums\Permission;
use App\Models\Role;
use App\Models\User;
use App\Policies\RolePolicy;
use DA\Admin\Models\Tenant;

function roleActor(?Permission $permission): User
{
    $tenant = Tenant::factory()->create();

    return User::factory()
        ->forTenant($tenant)
        ->withPermissions($permission === null ? [] : [$permission])
        ->create();
}

test('roles.view grants viewAny and view', function () {
    $actor = roleActor(Permission::RolesView);
    $role = Role::factory()->create(['tenant_id' => $actor->tenant_id]);
    $policy = new RolePolicy;

    expect($policy->viewAny($actor))->toBeTrue()
        ->and($policy->view($actor, $role))->toBeTrue();
});

test('roles.create grants create', function () {
    $actor = roleActor(Permission::RolesCreate);

    expect((new RolePolicy)->create($actor))->toBeTrue();
});

test('roles.update grants update', function () {
    $actor = roleActor(Permission::RolesUpdate);
    $role = Role::factory()->create(['tenant_id' => $actor->tenant_id]);

    expect((new RolePolicy)->update($actor, $role))->toBeTrue();
});

test('roles.delete grants delete', function () {
    $actor = roleActor(Permission::RolesDelete);
    $role = Role::factory()->create(['tenant_id' => $actor->tenant_id]);

    expect((new RolePolicy)->delete($actor, $role))->toBeTrue();
});

test('a user without the matching permission is denied', function (string $ability) {
    $actor = roleActor(null);
    $role = Role::factory()->create(['tenant_id' => $actor->tenant_id]);
    $policy = new RolePolicy;

    $allowed = match ($ability) {
        'viewAny' => $policy->viewAny($actor),
        'view' => $policy->view($actor, $role),
        'create' => $policy->create($actor),
        'update' => $policy->update($actor, $role),
        'delete' => $policy->delete($actor, $role),
    };

    expect($allowed)->toBeFalse();
})->with([
    'viewAny',
    'view',
    'create',
    'update',
    'delete',
]);
