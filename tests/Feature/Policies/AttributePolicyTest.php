<?php

use App\Enums\Permission;
use App\Models\User;
use DA\Admin\Models\Tenant;
use DA\Inventory\Models\Attribute;
use DA\Inventory\Policies\AttributePolicy;

function attributeActor(?Permission $permission): User
{
    $tenant = Tenant::factory()->create();

    return User::factory()
        ->forTenant($tenant)
        ->withPermissions($permission === null ? [] : [$permission])
        ->create();
}

test('attributes.view grants viewAny and view', function () {
    $actor = attributeActor(Permission::AttributesView);
    $attribute = Attribute::factory()->create(['tenant_id' => $actor->tenant_id]);
    $policy = new AttributePolicy;

    expect($policy->viewAny($actor))->toBeTrue()
        ->and($policy->view($actor, $attribute))->toBeTrue();
});

test('attributes.create grants create', function () {
    $actor = attributeActor(Permission::AttributesCreate);

    expect((new AttributePolicy)->create($actor))->toBeTrue();
});

test('attributes.update grants update', function () {
    $actor = attributeActor(Permission::AttributesUpdate);
    $attribute = Attribute::factory()->create(['tenant_id' => $actor->tenant_id]);

    expect((new AttributePolicy)->update($actor, $attribute))->toBeTrue();
});

test('attributes.delete grants delete', function () {
    $actor = attributeActor(Permission::AttributesDelete);
    $attribute = Attribute::factory()->create(['tenant_id' => $actor->tenant_id]);

    expect((new AttributePolicy)->delete($actor, $attribute))->toBeTrue();
});

test('a user without the matching permission is denied', function (string $ability) {
    $actor = attributeActor(null);
    $attribute = Attribute::factory()->create(['tenant_id' => $actor->tenant_id]);
    $policy = new AttributePolicy;

    $allowed = match ($ability) {
        'viewAny' => $policy->viewAny($actor),
        'view' => $policy->view($actor, $attribute),
        'create' => $policy->create($actor),
        'update' => $policy->update($actor, $attribute),
        'delete' => $policy->delete($actor, $attribute),
    };

    expect($allowed)->toBeFalse();
})->with([
    'viewAny',
    'view',
    'create',
    'update',
    'delete',
]);
