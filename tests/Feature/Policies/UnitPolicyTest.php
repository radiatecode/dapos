<?php

use App\Enums\Permission;
use App\Models\User;
use DA\Admin\Models\Tenant;
use DA\Inventory\Models\Unit;
use DA\Inventory\Policies\UnitPolicy;

function unitActor(?Permission $permission): User
{
    $tenant = Tenant::factory()->create();

    return User::factory()
        ->forTenant($tenant)
        ->withPermissions($permission === null ? [] : [$permission])
        ->create();
}

test('units.view grants viewAny and view', function () {
    $actor = unitActor(Permission::UnitsView);
    $unit = Unit::factory()->create(['tenant_id' => $actor->tenant_id]);
    $policy = new UnitPolicy;

    expect($policy->viewAny($actor))->toBeTrue()
        ->and($policy->view($actor, $unit))->toBeTrue();
});

test('units.create grants create', function () {
    $actor = unitActor(Permission::UnitsCreate);

    expect((new UnitPolicy)->create($actor))->toBeTrue();
});

test('units.update grants update', function () {
    $actor = unitActor(Permission::UnitsUpdate);
    $unit = Unit::factory()->create(['tenant_id' => $actor->tenant_id]);

    expect((new UnitPolicy)->update($actor, $unit))->toBeTrue();
});

test('units.delete grants delete', function () {
    $actor = unitActor(Permission::UnitsDelete);
    $unit = Unit::factory()->create(['tenant_id' => $actor->tenant_id]);

    expect((new UnitPolicy)->delete($actor, $unit))->toBeTrue();
});

test('a user without the matching permission is denied', function (string $ability) {
    $actor = unitActor(null);
    $unit = Unit::factory()->create(['tenant_id' => $actor->tenant_id]);
    $policy = new UnitPolicy;

    $allowed = match ($ability) {
        'viewAny' => $policy->viewAny($actor),
        'view' => $policy->view($actor, $unit),
        'create' => $policy->create($actor),
        'update' => $policy->update($actor, $unit),
        'delete' => $policy->delete($actor, $unit),
    };

    expect($allowed)->toBeFalse();
})->with([
    'viewAny',
    'view',
    'create',
    'update',
    'delete',
]);
