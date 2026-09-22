<?php

use App\Enums\Permission;
use DA\Admin\Models\Tenant;
use DA\Inventory\Enums\AttributeInputType;
use DA\Inventory\Models\Attribute;
use DA\Inventory\Models\AttributeValue;

describe('authentication and authorization', function () {
    it('returns 401 when no token is provided', function (string $method, string $uri) {
        $this->json($method, $uri, attributePayload())->assertUnauthorized();
    })->with([
        ['GET', '/api/v1/attributes'],
        ['POST', '/api/v1/attributes'],
        ['GET', '/api/v1/attributes/1'],
        ['PUT', '/api/v1/attributes/1'],
        ['DELETE', '/api/v1/attributes/1'],
    ]);

    it('returns 403 when the user lacks the required permission', function () {
        actingAsTenantUser(permissions: [Permission::BrandsView]);

        $this->getJson('/api/v1/attributes')->assertForbidden();
    });
});

describe('index and show', function () {
    it('lists attributes for the current tenant by sort order', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::AttributesView]);
        $second = Attribute::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Size',
            'sort_order' => 2,
        ]);
        $first = Attribute::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Color',
            'sort_order' => 1,
        ]);
        Attribute::factory()->create(['name' => 'Other tenant']);

        $this->getJson('/api/v1/attributes')
            ->assertOk()
            ->assertJsonPath('data.0.id', $first->id)
            ->assertJsonPath('data.1.id', $second->id)
            ->assertJsonMissing(['name' => 'Other tenant']);
    });

    it('returns 404 when tenant A requests a tenant B attribute', function () {
        $tenantA = Tenant::factory()->create();
        actingAsTenantUser($tenantA, [Permission::AttributesView]);
        $foreign = Attribute::factory()->create();

        $this->getJson('/api/v1/attributes/'.$foreign->id)->assertNotFound();
    });

    it('returns an attribute with its values', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::AttributesView]);
        $attribute = Attribute::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Color']);
        $value = AttributeValue::factory()->create([
            'tenant_id' => $tenant->id,
            'attribute_id' => $attribute->id,
            'value' => 'Red',
        ]);

        $this->getJson('/api/v1/attributes/'.$attribute->id)
            ->assertOk()
            ->assertJsonPath('data.id', $attribute->id)
            ->assertJsonPath('data.values.0.id', $value->id)
            ->assertJsonPath('data.values.0.value', 'Red');
    });
});

describe('store', function () {
    it('creates an attribute and generates a slug from the name', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::AttributesCreate]);

        $this->postJson('/api/v1/attributes', [
            'name' => 'Material',
            'input_type' => AttributeInputType::Select->value,
        ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Material')
            ->assertJsonPath('data.slug', 'material')
            ->assertJsonPath('data.input_type', AttributeInputType::Select->value);

        $this->assertDatabaseHas('attributes', [
            'name' => 'Material',
            'slug' => 'material',
            'tenant_id' => $tenant->id,
        ]);
    });

    it('returns 422 when the name is missing', function () {
        actingAsTenantUser(permissions: [Permission::AttributesCreate]);

        $this->postJson('/api/v1/attributes', attributePayload(['name' => '']))
            ->assertUnprocessable()
            ->assertJsonPath('errors.name.0', 'The name field is required.');
    });

    it('returns 422 when the slug is already taken', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::AttributesCreate]);
        Attribute::factory()->create([
            'tenant_id' => $tenant->id,
            'slug' => 'color',
        ]);

        $this->postJson('/api/v1/attributes', attributePayload())
            ->assertUnprocessable()
            ->assertJsonPath('errors.slug.0', 'The slug has already been taken.');
    });

    it('returns 422 when the input type is invalid', function () {
        actingAsTenantUser(permissions: [Permission::AttributesCreate]);

        $this->postJson('/api/v1/attributes', attributePayload([
            'input_type' => 'checkbox',
        ]))->assertUnprocessable()
            ->assertJsonValidationErrors(['input_type']);
    });
});

describe('update and destroy', function () {
    it('updates the attribute', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::AttributesUpdate]);
        $attribute = Attribute::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Old',
            'slug' => 'old',
        ]);

        $this->putJson('/api/v1/attributes/'.$attribute->id, [
            'name' => 'Updated',
            'slug' => 'updated',
            'input_type' => AttributeInputType::Multiselect->value,
        ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Updated')
            ->assertJsonPath('data.slug', 'updated')
            ->assertJsonPath('data.input_type', AttributeInputType::Multiselect->value);

        $this->assertDatabaseHas('attributes', [
            'id' => $attribute->id,
            'name' => 'Updated',
            'slug' => 'updated',
        ]);
    });

    it('deletes the attribute and its values', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::AttributesDelete]);
        $attribute = Attribute::factory()->create(['tenant_id' => $tenant->id]);
        $value = AttributeValue::factory()->create([
            'tenant_id' => $tenant->id,
            'attribute_id' => $attribute->id,
        ]);

        $this->deleteJson('/api/v1/attributes/'.$attribute->id)->assertNoContent();

        $this->assertDatabaseMissing('attributes', ['id' => $attribute->id]);
        $this->assertDatabaseMissing('attribute_values', ['id' => $value->id]);
    });
});
