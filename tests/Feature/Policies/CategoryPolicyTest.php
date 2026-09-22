<?php

use App\Enums\Permission;
use App\Models\User;
use DA\Admin\Models\Tenant;
use DA\Inventory\Models\Category;
use DA\Inventory\Policies\CategoryPolicy;

function categoryActor(?Permission $permission): User
{
    $tenant = Tenant::factory()->create();

    return User::factory()
        ->forTenant($tenant)
        ->withPermissions($permission === null ? [] : [$permission])
        ->create();
}

test('categories.view grants viewAny and view', function () {
    $actor = categoryActor(Permission::CategoriesView);
    $category = Category::factory()->create(['tenant_id' => $actor->tenant_id]);
    $policy = new CategoryPolicy;

    expect($policy->viewAny($actor))->toBeTrue()
        ->and($policy->view($actor, $category))->toBeTrue();
});

test('categories.create grants create', function () {
    $actor = categoryActor(Permission::CategoriesCreate);

    expect((new CategoryPolicy)->create($actor))->toBeTrue();
});

test('categories.update grants update', function () {
    $actor = categoryActor(Permission::CategoriesUpdate);
    $category = Category::factory()->create(['tenant_id' => $actor->tenant_id]);

    expect((new CategoryPolicy)->update($actor, $category))->toBeTrue();
});

test('categories.delete grants delete', function () {
    $actor = categoryActor(Permission::CategoriesDelete);
    $category = Category::factory()->create(['tenant_id' => $actor->tenant_id]);

    expect((new CategoryPolicy)->delete($actor, $category))->toBeTrue();
});

test('a user without the matching permission is denied', function (string $ability) {
    $actor = categoryActor(null);
    $category = Category::factory()->create(['tenant_id' => $actor->tenant_id]);
    $policy = new CategoryPolicy;

    $allowed = match ($ability) {
        'viewAny' => $policy->viewAny($actor),
        'view' => $policy->view($actor, $category),
        'create' => $policy->create($actor),
        'update' => $policy->update($actor, $category),
        'delete' => $policy->delete($actor, $category),
    };

    expect($allowed)->toBeFalse();
})->with([
    'viewAny',
    'view',
    'create',
    'update',
    'delete',
]);
