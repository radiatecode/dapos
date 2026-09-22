<?php

use App\Enums\Permission;
use DA\Admin\Models\Tenant;
use DA\Inventory\Models\Category;

describe('authentication and authorization', function () {
    it('returns 401 when no token is provided', function (string $method, string $uri) {
        $this->json($method, $uri, categoryPayload())->assertUnauthorized();
    })->with([
        ['GET', '/api/v1/categories'],
        ['POST', '/api/v1/categories'],
        ['GET', '/api/v1/categories/1'],
        ['PUT', '/api/v1/categories/1'],
        ['DELETE', '/api/v1/categories/1'],
    ]);

    it('returns 403 when the user lacks the required permission', function () {
        actingAsTenantUser(permissions: [Permission::BrandsView]);

        $this->getJson('/api/v1/categories')->assertForbidden();
    });
});

describe('index and show', function () {
    it('returns the category tree for the current tenant', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::CategoriesView]);
        $parent = Category::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Drinks',
            'sort_order' => 1,
        ]);
        $child = Category::factory()->childOf($parent)->create([
            'name' => 'Juices',
            'sort_order' => 0,
        ]);
        Category::factory()->create(['name' => 'Other tenant']);

        $this->getJson('/api/v1/categories')
            ->assertOk()
            ->assertJsonPath('data.0.id', $parent->id)
            ->assertJsonPath('data.0.children.0.id', $child->id)
            ->assertJsonMissing(['name' => 'Other tenant']);
    });

    it('returns 404 when tenant A requests a tenant B category', function () {
        $tenantA = Tenant::factory()->create();
        actingAsTenantUser($tenantA, [Permission::CategoriesView]);
        $foreign = Category::factory()->create();

        $this->getJson('/api/v1/categories/'.$foreign->id)->assertNotFound();
    });

    it('returns a category with its parent and children', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::CategoriesView]);
        $parent = Category::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Drinks']);
        $child = Category::factory()->childOf($parent)->create(['name' => 'Juices']);

        $this->getJson('/api/v1/categories/'.$parent->id)
            ->assertOk()
            ->assertJsonPath('data.id', $parent->id)
            ->assertJsonPath('data.children.0.id', $child->id);
    });
});

describe('store', function () {
    it('creates a category and generates a slug from the name', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::CategoriesCreate]);

        $this->postJson('/api/v1/categories', [
            'name' => 'Fresh Produce',
        ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Fresh Produce')
            ->assertJsonPath('data.slug', 'fresh-produce')
            ->assertJsonPath('data.is_active', true);

        $this->assertDatabaseHas('categories', [
            'name' => 'Fresh Produce',
            'slug' => 'fresh-produce',
            'tenant_id' => $tenant->id,
        ]);
    });

    it('nests a category under a parent from the same tenant', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::CategoriesCreate]);
        $parent = Category::factory()->create(['tenant_id' => $tenant->id]);

        $this->postJson('/api/v1/categories', categoryPayload([
            'name' => 'Sodas',
            'slug' => 'sodas',
            'parent_id' => $parent->id,
        ]))
            ->assertCreated()
            ->assertJsonPath('data.parent_id', $parent->id);
    });

    it('returns 422 when the name is missing', function () {
        actingAsTenantUser(permissions: [Permission::CategoriesCreate]);

        $this->postJson('/api/v1/categories', categoryPayload(['name' => '']))
            ->assertUnprocessable()
            ->assertJsonPath('errors.name.0', 'The name field is required.');
    });

    it('returns 422 when the slug is already taken', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::CategoriesCreate]);
        Category::factory()->create([
            'tenant_id' => $tenant->id,
            'slug' => 'beverages',
        ]);

        $this->postJson('/api/v1/categories', categoryPayload())
            ->assertUnprocessable()
            ->assertJsonPath('errors.slug.0', 'The slug has already been taken.');
    });

    it('returns 422 when the parent belongs to another tenant', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::CategoriesCreate]);
        $foreign = Category::factory()->create();

        $this->postJson('/api/v1/categories', categoryPayload([
            'parent_id' => $foreign->id,
        ]))
            ->assertUnprocessable()
            ->assertJsonPath('errors.parent_id.0', 'The selected parent category is invalid.');
    });
});

describe('update and destroy', function () {
    it('updates the category', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::CategoriesUpdate]);
        $category = Category::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Old',
            'slug' => 'old',
        ]);

        $this->putJson('/api/v1/categories/'.$category->id, [
            'name' => 'Updated',
            'slug' => 'updated',
        ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Updated')
            ->assertJsonPath('data.slug', 'updated');

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Updated',
            'slug' => 'updated',
        ]);
    });

    it('returns 422 when a category is nested under one of its descendants', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::CategoriesUpdate]);
        $parent = Category::factory()->create(['tenant_id' => $tenant->id]);
        $child = Category::factory()->childOf($parent)->create();

        $this->putJson('/api/v1/categories/'.$parent->id, [
            'name' => $parent->name,
            'parent_id' => $child->id,
        ])
            ->assertUnprocessable()
            ->assertJsonPath('errors.parent_id.0', 'A category cannot be nested under one of its descendants.');
    });

    it('returns 422 when a category is set as its own parent', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::CategoriesUpdate]);
        $category = Category::factory()->create(['tenant_id' => $tenant->id]);

        $this->putJson('/api/v1/categories/'.$category->id, [
            'name' => $category->name,
            'parent_id' => $category->id,
        ])
            ->assertUnprocessable()
            ->assertJsonPath('errors.parent_id.0', 'A category cannot be its own parent.');
    });

    it('deletes a leaf category', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::CategoriesDelete]);
        $category = Category::factory()->create(['tenant_id' => $tenant->id]);

        $this->deleteJson('/api/v1/categories/'.$category->id)->assertNoContent();

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    });

    it('returns 422 when deleting a category that has children', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::CategoriesDelete]);
        $parent = Category::factory()->create(['tenant_id' => $tenant->id]);
        Category::factory()->childOf($parent)->create();

        $this->deleteJson('/api/v1/categories/'.$parent->id)
            ->assertUnprocessable()
            ->assertJsonPath('errors.category.0', 'This category has child categories and cannot be deleted.');
    });
});
