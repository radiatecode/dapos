<?php

use App\Enums\Permission;
use App\Models\User;
use DA\Admin\Models\Tenant;
use DA\Expense\Models\Expense;
use DA\Expense\Models\ExpenseCategory;
use DA\Inventory\Models\Store;
use Laravel\Sanctum\Sanctum;

describe('authentication and authorization', function () {
    it('returns 401 when no token is provided', function (string $method, string $uri) {
        $this->json($method, $uri, expenseCategoryPayload())->assertUnauthorized();
    })->with([
        ['GET', '/api/v1/expense-categories'],
        ['POST', '/api/v1/expense-categories'],
        ['GET', '/api/v1/expense-categories/1'],
        ['PUT', '/api/v1/expense-categories/1'],
        ['POST', '/api/v1/expense-categories/delete'],
    ]);

    it('returns 403 when the user lacks the required permission', function () {
        actingAsTenantUser(permissions: [Permission::ExpensesView]);

        $this->getJson('/api/v1/expense-categories')->assertForbidden();
    });
});

describe('index and show', function () {
    it('lists expense categories for the current tenant newest first', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::ExpenseCategoriesView]);
        $older = ExpenseCategory::factory()->create(['tenant_id' => $tenant->id, 'category_name' => 'Older']);
        $newer = ExpenseCategory::factory()->create(['tenant_id' => $tenant->id, 'category_name' => 'Newer']);
        ExpenseCategory::factory()->create(['category_name' => 'Other tenant']);

        $this->getJson('/api/v1/expense-categories')
            ->assertOk()
            ->assertJsonPath('data.0.id', $newer->id)
            ->assertJsonPath('data.1.id', $older->id)
            ->assertJsonMissing(['category_name' => 'Other tenant']);
    });

    it('filters expense categories by name', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::ExpenseCategoriesView]);
        ExpenseCategory::factory()->create(['tenant_id' => $tenant->id, 'category_name' => 'Shop Rent']);
        ExpenseCategory::factory()->create(['tenant_id' => $tenant->id, 'category_name' => 'Utilities']);

        $this->getJson('/api/v1/expense-categories?category_name=Rent&limit=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.category_name', 'Shop Rent')
            ->assertJsonPath('meta.per_page', 1);
    });

    it('returns 404 when tenant A requests a tenant B expense category', function () {
        $tenantA = Tenant::factory()->create();
        actingAsTenantUser($tenantA, [Permission::ExpenseCategoriesView]);
        $foreign = ExpenseCategory::factory()->create();

        $this->getJson('/api/v1/expense-categories/'.$foreign->id)->assertNotFound();
    });
});

describe('store', function () {
    it('creates an expense category for the authenticated tenant', function () {
        $tenant = Tenant::factory()->create();
        $user = actingAsTenantUser($tenant, [Permission::ExpenseCategoriesCreate]);

        $this->postJson('/api/v1/expense-categories', expenseCategoryPayload())
            ->assertCreated()
            ->assertJsonPath('data.category_name', 'Rent')
            ->assertJsonPath('data.is_active', true)
            ->assertJsonPath('data.status', 'Active')
            ->assertJsonPath('data.created_by', $user->id)
            ->assertJsonPath('data.updated_by', $user->id);

        $this->assertDatabaseHas('expense_categories', [
            'category_name' => 'Rent',
            'tenant_id' => $tenant->id,
            'is_active' => true,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
    });

    it('stores an inactive expense category', function () {
        actingAsTenantUser(permissions: [Permission::ExpenseCategoriesCreate]);

        $this->postJson('/api/v1/expense-categories', expenseCategoryPayload([
            'is_active' => false,
        ]))
            ->assertCreated()
            ->assertJsonPath('data.is_active', false)
            ->assertJsonPath('data.status', 'Inactive');
    });

    it('returns 422 when the category name is missing', function () {
        actingAsTenantUser(permissions: [Permission::ExpenseCategoriesCreate]);

        $this->postJson('/api/v1/expense-categories', [])
            ->assertUnprocessable()
            ->assertJsonPath('errors.category_name.0', 'The category name field is required.');
    });

    it('returns 422 when the category name is already used by the tenant', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::ExpenseCategoriesCreate]);
        ExpenseCategory::factory()->create([
            'tenant_id' => $tenant->id,
            'category_name' => 'Rent',
        ]);

        $this->postJson('/api/v1/expense-categories', expenseCategoryPayload())
            ->assertUnprocessable()
            ->assertJsonPath('errors.category_name.0', 'The category name has already been taken.');

        $this->assertDatabaseCount('expense_categories', 1);
    });

    it('allows the same category name for another tenant', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::ExpenseCategoriesCreate]);
        ExpenseCategory::factory()->create(['category_name' => 'Rent']);

        $this->postJson('/api/v1/expense-categories', expenseCategoryPayload())
            ->assertCreated()
            ->assertJsonPath('data.category_name', 'Rent');

        $this->assertDatabaseHas('expense_categories', [
            'category_name' => 'Rent',
            'tenant_id' => $tenant->id,
        ]);
    });
});

describe('update and destroy', function () {
    it('updates the expense category', function () {
        $tenant = Tenant::factory()->create();
        $user = actingAsTenantUser($tenant, [Permission::ExpenseCategoriesUpdate]);
        $category = ExpenseCategory::factory()->create([
            'tenant_id' => $tenant->id,
            'category_name' => 'Old',
        ]);

        $this->putJson('/api/v1/expense-categories/'.$category->id, expenseCategoryPayload([
            'category_name' => 'Utilities',
            'is_active' => false,
        ]))
            ->assertOk()
            ->assertJsonPath('data.category_name', 'Utilities')
            ->assertJsonPath('data.is_active', false)
            ->assertJsonPath('data.updated_by', $user->id);

        $this->assertDatabaseHas('expense_categories', [
            'id' => $category->id,
            'category_name' => 'Utilities',
            'is_active' => false,
            'tenant_id' => $tenant->id,
            'updated_by' => $user->id,
        ]);
    });

    it('returns 422 when the updated name belongs to another category', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::ExpenseCategoriesUpdate]);
        ExpenseCategory::factory()->create([
            'tenant_id' => $tenant->id,
            'category_name' => 'Utilities',
        ]);
        $category = ExpenseCategory::factory()->create([
            'tenant_id' => $tenant->id,
            'category_name' => 'Rent',
        ]);

        $this->putJson('/api/v1/expense-categories/'.$category->id, expenseCategoryPayload([
            'category_name' => 'Utilities',
        ]))->assertUnprocessable()
            ->assertJsonPath('errors.category_name.0', 'The category name has already been taken.');

        $this->assertDatabaseHas('expense_categories', [
            'id' => $category->id,
            'category_name' => 'Rent',
        ]);
    });

    it('returns 404 when updating another tenants expense category', function () {
        $tenantA = Tenant::factory()->create();
        actingAsTenantUser($tenantA, [Permission::ExpenseCategoriesUpdate]);
        $foreign = ExpenseCategory::factory()->create();

        $this->putJson('/api/v1/expense-categories/'.$foreign->id, expenseCategoryPayload())
            ->assertNotFound();
    });

    it('returns 422 when a non-creator updates an expense category', function () {
        $tenant = Tenant::factory()->create();
        $creator = User::factory()->forTenant($tenant)->create();
        $category = ExpenseCategory::factory()->create([
            'tenant_id' => $tenant->id,
            'created_by' => $creator->id,
            'category_name' => 'Rent',
        ]);
        actingAsTenantUser($tenant, [Permission::ExpenseCategoriesUpdate, Permission::OnlyCreatorUpdate]);

        $this->putJson('/api/v1/expense-categories/'.$category->id, expenseCategoryPayload([
            'category_name' => 'Utilities',
        ]))->assertUnprocessable()
            ->assertJsonPath('errors.expense_category.0', 'You are not authorized to update this expense category.');

        $this->assertDatabaseHas('expense_categories', [
            'id' => $category->id,
            'category_name' => 'Rent',
        ]);
    });

    it('deletes the expense category', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::ExpenseCategoriesDelete]);
        $category = ExpenseCategory::factory()->create(['tenant_id' => $tenant->id]);

        $this->postJson('/api/v1/expense-categories/delete', [
            'action' => 'delete',
            'ids' => [$category->id],
        ])->assertOk()
            ->assertJsonPath('message', 'Expense category deleted successfully');

        $this->assertDatabaseMissing('expense_categories', ['id' => $category->id]);
    });

    it('rejects deleting an expense category that is used by an expense', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::ExpenseCategoriesDelete]);
        $category = ExpenseCategory::factory()->create(['tenant_id' => $tenant->id]);
        Expense::factory()->create([
            'tenant_id' => $tenant->id,
            'category_id' => $category->id,
        ]);

        $this->postJson('/api/v1/expense-categories/delete', [
            'action' => 'delete',
            'ids' => [$category->id],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors([
                'ids' => 'This expense category cannot be deleted because it is used by an expense.',
            ]);

        $this->assertDatabaseHas('expense_categories', ['id' => $category->id]);
    });

    it('rejects deleting a category used by an expense in another allocated store', function () {
        $tenant = Tenant::factory()->create();
        $allowed = Store::factory()->create(['tenant_id' => $tenant->id]);
        $other = Store::factory()->create(['tenant_id' => $tenant->id]);
        $category = ExpenseCategory::factory()->create(['tenant_id' => $tenant->id]);
        Expense::factory()->create([
            'tenant_id' => $tenant->id,
            'category_id' => $category->id,
            'store_id' => $other->id,
        ]);
        $cashier = User::factory()->forTenant($tenant)->withPermissions([
            Permission::ExpenseCategoriesDelete,
        ])->create();
        $cashier->stores()->attach($allowed->id, ['tenant_id' => $tenant->id]);
        grantSubscriptionFeatures($tenant);
        Sanctum::actingAs($cashier);

        $this->postJson('/api/v1/expense-categories/delete', [
            'action' => 'delete',
            'ids' => [$category->id],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors([
                'ids' => 'This expense category cannot be deleted because it is used by an expense.',
            ]);

        $this->assertDatabaseHas('expense_categories', ['id' => $category->id]);
    });

    it('rejects a delete request with an invalid action', function () {
        actingAsTenantUser(permissions: [Permission::ExpenseCategoriesDelete]);

        $this->postJson('/api/v1/expense-categories/delete', [
            'action' => 'archive',
            'ids' => [1],
        ])->assertBadRequest();
    });
});
