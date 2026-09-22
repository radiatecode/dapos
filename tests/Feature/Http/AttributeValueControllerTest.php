<?php

use App\Enums\Permission;
use DA\Admin\Models\Tenant;
use DA\Inventory\Models\Attribute;
use DA\Inventory\Models\AttributeValue;

describe('authentication and authorization', function () {
    it('returns 401 when no token is provided', function (string $method, string $uri) {
        $this->json($method, $uri, attributeValuePayload())->assertUnauthorized();
    })->with([
        ['GET', '/api/v1/attributes/1/values'],
        ['POST', '/api/v1/attributes/1/values'],
        ['GET', '/api/v1/attributes/1/values/1'],
        ['PUT', '/api/v1/attributes/1/values/1'],
        ['DELETE', '/api/v1/attributes/1/values/1'],
    ]);

    it('returns 403 when the user lacks the required permission', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::BrandsView]);
        $attribute = Attribute::factory()->create(['tenant_id' => $tenant->id]);

        $this->getJson('/api/v1/attributes/'.$attribute->id.'/values')->assertForbidden();
    });
});

describe('index and show', function () {
    it('lists values for the current attribute by sort order', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::AttributesView]);
        $attribute = Attribute::factory()->create(['tenant_id' => $tenant->id]);
        $second = AttributeValue::factory()->create([
            'tenant_id' => $tenant->id,
            'attribute_id' => $attribute->id,
            'value' => 'Blue',
            'sort_order' => 2,
        ]);
        $first = AttributeValue::factory()->create([
            'tenant_id' => $tenant->id,
            'attribute_id' => $attribute->id,
            'value' => 'Red',
            'sort_order' => 1,
        ]);
        $other = Attribute::factory()->create(['tenant_id' => $tenant->id]);
        AttributeValue::factory()->create([
            'tenant_id' => $tenant->id,
            'attribute_id' => $other->id,
            'value' => 'Other attribute',
        ]);

        $this->getJson('/api/v1/attributes/'.$attribute->id.'/values')
            ->assertOk()
            ->assertJsonPath('data.0.id', $first->id)
            ->assertJsonPath('data.1.id', $second->id)
            ->assertJsonMissing(['value' => 'Other attribute']);
    });

    it('returns 404 when tenant A requests a tenant B attribute', function () {
        $tenantA = Tenant::factory()->create();
        actingAsTenantUser($tenantA, [Permission::AttributesView]);
        $foreign = Attribute::factory()->create();

        $this->getJson('/api/v1/attributes/'.$foreign->id.'/values')->assertNotFound();
    });

    it('returns 404 when a value belongs to another attribute', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::AttributesView]);
        $attribute = Attribute::factory()->create(['tenant_id' => $tenant->id]);
        $other = Attribute::factory()->create(['tenant_id' => $tenant->id]);
        $value = AttributeValue::factory()->create([
            'tenant_id' => $tenant->id,
            'attribute_id' => $other->id,
        ]);

        $this->getJson('/api/v1/attributes/'.$attribute->id.'/values/'.$value->id)->assertNotFound();
    });
});

describe('store', function () {
    it('creates an attribute value and generates a slug from the value', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::AttributesCreate]);
        $attribute = Attribute::factory()->create(['tenant_id' => $tenant->id]);

        $this->postJson('/api/v1/attributes/'.$attribute->id.'/values', [
            'value' => 'Dark Red',
        ])
            ->assertCreated()
            ->assertJsonPath('data.value', 'Dark Red')
            ->assertJsonPath('data.slug', 'dark-red')
            ->assertJsonPath('data.attribute_id', $attribute->id);

        $this->assertDatabaseHas('attribute_values', [
            'value' => 'Dark Red',
            'slug' => 'dark-red',
            'attribute_id' => $attribute->id,
            'tenant_id' => $tenant->id,
        ]);
    });

    it('returns 422 when the value is missing', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::AttributesCreate]);
        $attribute = Attribute::factory()->create(['tenant_id' => $tenant->id]);

        $this->postJson('/api/v1/attributes/'.$attribute->id.'/values', attributeValuePayload(['value' => '']))
            ->assertUnprocessable()
            ->assertJsonPath('errors.value.0', 'The value field is required.');
    });

    it('returns 422 when the slug is already taken for the attribute', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::AttributesCreate]);
        $attribute = Attribute::factory()->create(['tenant_id' => $tenant->id]);
        AttributeValue::factory()->create([
            'tenant_id' => $tenant->id,
            'attribute_id' => $attribute->id,
            'slug' => 'red',
        ]);

        $this->postJson('/api/v1/attributes/'.$attribute->id.'/values', attributeValuePayload())
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
            'tenant_id' => $tenant->id,
            'attribute_id' => $attribute->id,
            'value' => 'Old',
            'slug' => 'old',
        ]);

        $this->putJson('/api/v1/attributes/'.$attribute->id.'/values/'.$value->id, [
            'value' => 'Updated',
            'slug' => 'updated',
        ])
            ->assertOk()
            ->assertJsonPath('data.value', 'Updated')
            ->assertJsonPath('data.slug', 'updated');

        $this->assertDatabaseHas('attribute_values', [
            'id' => $value->id,
            'value' => 'Updated',
            'slug' => 'updated',
        ]);
    });

    it('deletes the attribute value', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::AttributesDelete]);
        $attribute = Attribute::factory()->create(['tenant_id' => $tenant->id]);
        $value = AttributeValue::factory()->create([
            'tenant_id' => $tenant->id,
            'attribute_id' => $attribute->id,
        ]);

        $this->deleteJson('/api/v1/attributes/'.$attribute->id.'/values/'.$value->id)->assertNoContent();

        $this->assertDatabaseMissing('attribute_values', ['id' => $value->id]);
    });
});
