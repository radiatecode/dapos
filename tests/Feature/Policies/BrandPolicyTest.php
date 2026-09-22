<?php

use App\Enums\Permission;
use App\Models\User;
use DA\Admin\Models\Tenant;
use DA\Inventory\Models\Brand;
use DA\Inventory\Policies\BrandPolicy;

function brandActor(?Permission $permission): User
{
    $tenant = Tenant::factory()->create();

    return User::factory()
        ->forTenant($tenant)
        ->withPermissions($permission === null ? [] : [$permission])
        ->create();
}

test('brands.view grants viewAny and view', function () {
    $actor = brandActor(Permission::BrandsView);
    $brand = Brand::factory()->create(['tenant_id' => $actor->tenant_id]);
    $policy = new BrandPolicy;

    expect($policy->viewAny($actor))->toBeTrue()
        ->and($policy->view($actor, $brand))->toBeTrue();
});

test('brands.create grants create', function () {
    $actor = brandActor(Permission::BrandsCreate);

    expect((new BrandPolicy)->create($actor))->toBeTrue();
});

test('brands.update grants update', function () {
    $actor = brandActor(Permission::BrandsUpdate);
    $brand = Brand::factory()->create(['tenant_id' => $actor->tenant_id]);

    expect((new BrandPolicy)->update($actor, $brand))->toBeTrue();
});

test('brands.delete grants delete', function () {
    $actor = brandActor(Permission::BrandsDelete);
    $brand = Brand::factory()->create(['tenant_id' => $actor->tenant_id]);

    expect((new BrandPolicy)->delete($actor, $brand))->toBeTrue();
});

test('a user without the matching permission is denied', function (string $ability) {
    $actor = brandActor(null);
    $brand = Brand::factory()->create(['tenant_id' => $actor->tenant_id]);
    $policy = new BrandPolicy;

    $allowed = match ($ability) {
        'viewAny' => $policy->viewAny($actor),
        'view' => $policy->view($actor, $brand),
        'create' => $policy->create($actor),
        'update' => $policy->update($actor, $brand),
        'delete' => $policy->delete($actor, $brand),
    };

    expect($allowed)->toBeFalse();
})->with([
    'viewAny',
    'view',
    'create',
    'update',
    'delete',
]);
