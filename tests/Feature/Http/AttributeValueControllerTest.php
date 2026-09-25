<?php

use App\Enums\Permission;
use DA\Admin\Models\Tenant;
use DA\Inventory\Models\Attribute;
use DA\Inventory\Models\AttributeValue;

describe('authentication and authorization', function () {
    it('returns 401 when no token is provided', function (string $method, string $uri) {
        $this->json($method, $uri, attributeValuePayload(['attribute_id' => 1]))->assertUnauthorized();
    })->with([
        ['GET', '/api/v1/attributes/values'],
        ['POST', '/api/v1/attributes/values'],
        ['GET', '/api/v1/attributes/values/1'],
        ['PUT', '/api/v1/attributes/values/1'],
        ['POST', '/api/v1/attributes/values/delete'],
    ]);

    it('returns 403 when the user lacks the required permission', function () {
        actingAsTenantUser(permissions: [Permission::BrandsView]);

        $this->getJson('/api/v1/attributes/values')->assertForbidden();
    });
});

describe('index and show', function () {
    it('lists attribute values for the current tenant and includes the attribute name', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::AttributesView]);
        $attribute = Attribute::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Color',
        ]);
        $first = AttributeValue::factory()->create([
            'attribute_id' => $attribute->id,
            'value' => 'Red',
            'sort_order' => 1,
        ]);
        $second = AttributeValue::factory()->create([
            'attribute_id' => $attribute->id,
            'value' => 'Blue',
            'sort_order' => 2,
        ]);
        AttributeValue::factory()->create(['value' => 'Other tenant']);

        $this->getJson('/api/v1/attributes/values')
            ->assertOk()
            ->assertJsonPath('data.0.id', $first->id)
            ->assertJsonPath('data.0.attribute_name', 'Color')
            ->assertJsonPath('data.1.id', $second->id)
            ->assertJsonMissing(['value' => 'Other tenant']);
    });

    it('filters attribute values by attribute id', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::AttributesView]);
        $color = Attribute::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Color',
        ]);
        $size = Attribute::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Size',
        ]);
        AttributeValue::factory()->create([
            'attribute_id' => $color->id,
            'value' => 'Red',
        ]);
        AttributeValue::factory()->create([
            'attribute_id' => $size->id,
            'value' => 'Large',
        ]);

        $this->getJson('/api/v1/attributes/values?attribute_id='.$color->id)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.value', 'Red')
            ->assertJsonPath('data.0.attribute_name', 'Color');
    });

    it('filters attribute values by value', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::AttributesView]);
        $attribute = Attribute::factory()->create(['tenant_id' => $tenant->id]);
        AttributeValue::factory()->create([
            'attribute_id' => $attribute->id,
            'value' => 'Crimson',
        ]);
        AttributeValue::factory()->create([
            'attribute_id' => $attribute->id,
            'value' => 'Navy',
        ]);

        $this->getJson('/api/v1/attributes/values?value=Crim')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.value', 'Crimson');
    });

    it('returns 404 when tenant A requests a tenant B attribute value', function () {
        $tenantA = Tenant::factory()->create();
        actingAsTenantUser($tenantA, [Permission::AttributesView]);
        $foreign = AttributeValue::factory()->create();

        $this->getJson('/api/v1/attributes/values/'.$foreign->id)->assertNotFound();
    });
});

describe('store', function () {
    it('creates an attribute value for the selected attribute', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::AttributesCreate]);
        $attribute = Attribute::factory()->create(['tenant_id' => $tenant->id]);

        $this->postJson('/api/v1/attributes/values', attributeValuePayload([
            'attribute_id' => $attribute->id,
        ]))
            ->assertCreated()
            ->assertJsonPath('data.value', 'Red')
            ->assertJsonPath('data.slug', 'red')
            ->assertJsonPath('data.attribute_id', $attribute->id)
            ->assertJsonPath('data.is_active', true);

        $this->assertDatabaseHas('attribute_values', [
            'attribute_id' => $attribute->id,
            'value' => 'Red',
            'slug' => 'red',
            'tenant_id' => $tenant->id,
        ]);
    });

    it('returns 422 when required fields are missing', function () {
        actingAsTenantUser(permissions: [Permission::AttributesCreate]);

        $this->postJson('/api/v1/attributes/values', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['attribute_id', 'value'])
            ->assertJsonPath('errors.value.0', 'The value field is required.');
    });

    it('returns 422 when the slug is already taken for the attribute', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::AttributesCreate]);
        $attribute = Attribute::factory()->create(['tenant_id' => $tenant->id]);
        AttributeValue::factory()->create([
            'attribute_id' => $attribute->id,
            'slug' => 'red',
        ]);

        $this->postJson('/api/v1/attributes/values', attributeValuePayload([
            'attribute_id' => $attribute->id,
        ]))
            ->assertUnprocessable()
            ->assertJsonPath('errors.slug.0', 'The slug has already been taken.');
    });
});

describe('update and destroy', function () {
    it('updates the attribute value', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::AttributesUpdate]);
        $attribute = Attribute::factory()->create(['tenant_id' => $tenant->id]);
        $value = AttributeValue::factory()->create([
            'attribute_id' => $attribute->id,
            'value' => 'Old',
            'slug' => 'old',
        ]);

        $this->putJson('/api/v1/attributes/values/'.$value->id, attributeValuePayload([
            'attribute_id' => $attribute->id,
            'value' => 'Crimson',
            'slug' => 'crimson',
        ]))
            ->assertOk()
            ->assertJsonPath('data.value', 'Crimson')
            ->assertJsonPath('data.slug', 'crimson');

        $this->assertDatabaseHas('attribute_values', [
            'id' => $value->id,
            'value' => 'Crimson',
            'slug' => 'crimson',
        ]);
    });

    it('deletes the attribute value', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::AttributesDelete]);
        $attribute = Attribute::factory()->create(['tenant_id' => $tenant->id]);
        $value = AttributeValue::factory()->create(['attribute_id' => $attribute->id]);

        $this->postJson('/api/v1/attributes/values/delete', [
            'action' => 'delete',
            'ids' => [$value->id],
        ])->assertOk();

        $this->assertDatabaseMissing('attribute_values', ['id' => $value->id]);
    });

    it('rejects a delete request with an invalid action', function () {
        actingAsTenantUser(permissions: [Permission::AttributesDelete]);

        $this->postJson('/api/v1/attributes/values/delete', [
            'action' => 'archive',
            'ids' => [1],
        ])->assertStatus(400);
    });
});
