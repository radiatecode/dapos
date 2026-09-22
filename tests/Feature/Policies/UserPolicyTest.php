<?php

use App\Enums\Permission;
use App\Models\User;
use App\Policies\UserPolicy;
use DA\Admin\Models\Tenant;

function userWithPermission(?Permission $permission): User
{
    $tenant = Tenant::factory()->create();

    return User::factory()
        ->forTenant($tenant)
        ->withPermissions($permission === null ? [] : [$permission])
        ->create();
}

test('users.view grants viewAny and view', function () {
    $actor = userWithPermission(Permission::UsersView);
    $user = User::factory()->forTenant($actor->tenant)->create();
    $policy = new UserPolicy;

    expect($policy->viewAny($actor))->toBeTrue()
        ->and($policy->view($actor, $user))->toBeTrue();
});

test('users.create grants create', function () {
    $actor = userWithPermission(Permission::UsersCreate);

    expect((new UserPolicy)->create($actor))->toBeTrue();
});

test('users.update grants update, updatePassword, updatePhoto, and assignRoles', function () {
    $actor = userWithPermission(Permission::UsersUpdate);
    $user = User::factory()->forTenant($actor->tenant)->create();
    $policy = new UserPolicy;

    expect($policy->update($actor, $user))->toBeTrue()
        ->and($policy->updatePassword($actor, $user))->toBeTrue()
        ->and($policy->updatePhoto($actor, $user))->toBeTrue()
        ->and($policy->assignRoles($actor, $user))->toBeTrue();
});

test('users.delete grants delete for another user', function () {
    $actor = userWithPermission(Permission::UsersDelete);
    $user = User::factory()->forTenant($actor->tenant)->create();

    expect((new UserPolicy)->delete($actor, $user))->toBeTrue();
});

test('users.delete does not grant delete for the actor', function () {
    $actor = userWithPermission(Permission::UsersDelete);

    expect((new UserPolicy)->delete($actor, $actor))->toBeFalse();
});

test('a user without the matching permission is denied', function (string $ability) {
    $actor = userWithPermission(null);
    $user = User::factory()->forTenant($actor->tenant)->create();
    $policy = new UserPolicy;

    $allowed = match ($ability) {
        'viewAny' => $policy->viewAny($actor),
        'view' => $policy->view($actor, $user),
        'create' => $policy->create($actor),
        'update' => $policy->update($actor, $user),
        'delete' => $policy->delete($actor, $user),
        'updatePassword' => $policy->updatePassword($actor, $user),
        'updatePhoto' => $policy->updatePhoto($actor, $user),
        'assignRoles' => $policy->assignRoles($actor, $user),
    };

    expect($allowed)->toBeFalse();
})->with([
    'viewAny',
    'view',
    'create',
    'update',
    'delete',
    'updatePassword',
    'updatePhoto',
    'assignRoles',
]);
