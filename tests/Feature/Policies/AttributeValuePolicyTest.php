<?php

use App\Enums\Permission;
use App\Models\User;
use DA\Admin\Models\Tenant;
use DA\Inventory\Models\Attribute;
use DA\Inventory\Models\AttributeValue;
use DA\Inventory\Policies\AttributeValuePolicy;

function attributeValueActor(?Permission $permission): User
{
    $tenant = Tenant::factory()->create();

    return User::factory()
        ->forTenant($tenant)
        ->withPermissions($permission === null ? [] : [$permission])
        ->create();
}

test('attributes.view grants viewAny and view', function () {
    $actor = attributeValueActor(Permission::AttributesView);
    $attribute = Attribute::factory()->create(['tenant_id' => $actor->tenant_id]);
    $value = AttributeValue::factory()->create([
        'tenant_id' => $actor->tenant_id,
        'attribute_id' => $attribute->id,
    ]);
    $policy = new AttributeValuePolicy;

    expect($policy->viewAny($actor))->toBeTrue()
        ->and($policy->view($actor, $value))->toBeTrue();
});

test('attributes.create grants create', function () {
    $actor = attributeValueActor(Permission::AttributesCreate);

    expect((new AttributeValuePolicy)->create($actor))->toBeTrue();
});

test('attributes.update grants update', function () {
    $actor = attributeValueActor(Permission::AttributesUpdate);
    $attribute = Attribute::factory()->create(['tenant_id' => $actor->tenant_id]);
    $value = AttributeValue::factory()->create([
        'tenant_id' => $actor->tenant_id,
        'attribute_id' => $attribute->id,
    ]);

    expect((new AttributeValuePolicy)->update($actor, $value))->toBeTrue();
});

test('attributes.delete grants delete', function () {
    $actor = attributeValueActor(Permission::AttributesDelete);
    $attribute = Attribute::factory()->create(['tenant_id' => $actor->tenant_id]);
    $value = AttributeValue::factory()->create([
        'tenant_id' => $actor->tenant_id,
        'attribute_id' => $attribute->id,
    ]);

    expect((new AttributeValuePolicy)->delete($actor, $value))->toBeTrue();
});

test('a user without the matching permission is denied', function (string $ability) {
    $actor = attributeValueActor(null);
    $attribute = Attribute::factory()->create(['tenant_id' => $actor->tenant_id]);
    $value = AttributeValue::factory()->create([
        'tenant_id' => $actor->tenant_id,
        'attribute_id' => $attribute->id,
    ]);
    $policy = new AttributeValuePolicy;

    $allowed = match ($ability) {
        'viewAny' => $policy->viewAny($actor),
        'view' => $policy->view($actor, $value),
        'create' => $policy->create($actor),
        'update' => $policy->update($actor, $value),
        'delete' => $policy->delete($actor, $value),
    };

    expect($allowed)->toBeFalse();
})->with([
    'viewAny',
    'view',
    'create',
    'update',
    'delete',
]);
