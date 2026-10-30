<?php

use App\Enums\Permission;
use App\Models\User;
use DA\Admin\Enums\Features;
use DA\Admin\Models\Tenant;
use DA\Expense\Models\Expense;
use DA\Expense\Models\ExpenseCategory;
use DA\Inventory\Models\Store;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

describe('authentication and authorization', function () {
    it('returns 401 when no token is provided', function (string $method, string $uri) {
        $this->json($method, $uri, expensePayload())->assertUnauthorized();
    })->with([
        ['GET', '/api/v1/expenses'],
        ['GET', '/api/v1/expenses/summary'],
        ['POST', '/api/v1/expenses'],
        ['GET', '/api/v1/expenses/1'],
        ['POST', '/api/v1/expenses/1'],
        ['PUT', '/api/v1/expenses/1/status'],
        ['POST', '/api/v1/expenses/delete'],
    ]);

    it('returns 403 when the subscription does not include expenses', function () {
        $user = actingAsTenantUser(permissions: [Permission::ExpensesView]);
        $subscription = $user->tenant->subscription;
        $subscription->features = collect($subscription->features ?? [])
            ->map(fn (mixed $feature): string => $feature instanceof BackedEnum ? $feature->value : (string) $feature)
            ->reject(fn (string $feature): bool => $feature === Features::EXPENSES->value)
            ->values()
            ->all();
        $subscription->save();
        $user->unsetRelation('tenant');

        $this->getJson('/api/v1/expenses')
            ->assertForbidden()
            ->assertJsonPath('message', 'This feature is not available on the current subscription.');
    });

    it('returns 403 when the user lacks the required permission', function () {
        actingAsTenantUser(permissions: [Permission::ExpenseCategoriesView]);

        $this->getJson('/api/v1/expenses')->assertForbidden();
        $this->getJson('/api/v1/expenses/summary')->assertForbidden();
    });

    it('returns 403 when the user cannot approve an expense', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::ExpensesView]);
        $expense = Expense::factory()->create(['tenant_id' => $tenant->id]);

        $this->putJson('/api/v1/expenses/'.$expense->id.'/status', [
            'status' => 'approved',
        ])->assertForbidden();

        $this->assertDatabaseHas('expenses', [
            'id' => $expense->id,
            'status' => 'pending',
            'approval_time' => null,
        ]);
    });
});

describe('index and show', function () {
    it('lists expenses for the current tenant newest first', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::ExpensesView]);
        $older = Expense::factory()->create([
            'tenant_id' => $tenant->id,
            'description' => 'Older bill',
        ]);
        $newer = Expense::factory()->create([
            'tenant_id' => $tenant->id,
            'description' => 'Newer bill',
        ]);
        Expense::factory()->create(['description' => 'Other tenant']);

        $this->getJson('/api/v1/expenses')
            ->assertOk()
            ->assertJsonPath('data.0.id', $newer->id)
            ->assertJsonPath('data.1.id', $older->id)
            ->assertJsonMissing(['description' => 'Other tenant']);
    });

    it('filters expenses by status', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::ExpensesView]);
        Expense::factory()->create(['tenant_id' => $tenant->id, 'description' => 'Waiting']);
        $approved = Expense::factory()->approved()->create([
            'tenant_id' => $tenant->id,
            'description' => 'Paid',
        ]);

        $this->getJson('/api/v1/expenses?status=approved')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $approved->id)
            ->assertJsonPath('data.0.status', 'approved');
    });

    it('returns 422 when the status filter is invalid', function () {
        actingAsTenantUser(permissions: [Permission::ExpensesView]);

        $this->getJson('/api/v1/expenses?status=posted')->assertUnprocessable();
    });

    it('returns 404 when tenant A requests a tenant B expense', function () {
        $tenantA = Tenant::factory()->create();
        actingAsTenantUser($tenantA, [Permission::ExpensesView]);
        $foreign = Expense::factory()->create();

        $this->getJson('/api/v1/expenses/'.$foreign->id)->assertNotFound();
    });

    it('returns 404 when the expense store is not allocated to the user', function () {
        $tenant = Tenant::factory()->create();
        $allowed = Store::factory()->create(['tenant_id' => $tenant->id]);
        $blocked = Store::factory()->create(['tenant_id' => $tenant->id]);
        $hidden = Expense::factory()->create([
            'tenant_id' => $tenant->id,
            'store_id' => $blocked->id,
            'description' => 'Hidden bill',
        ]);
        Expense::factory()->create([
            'tenant_id' => $tenant->id,
            'store_id' => $allowed->id,
            'description' => 'Visible bill',
        ]);
        $cashier = User::factory()->forTenant($tenant)->withPermissions([
            Permission::ExpensesView,
        ])->create();
        $cashier->stores()->attach($allowed->id, ['tenant_id' => $tenant->id]);
        grantSubscriptionFeatures($tenant);
        Sanctum::actingAs($cashier);

        $this->getJson('/api/v1/expenses')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonMissing(['description' => 'Hidden bill']);

        $this->getJson('/api/v1/expenses/'.$hidden->id)->assertNotFound();
    });
});

describe('summary', function () {
    it('returns totals and category amounts for the authenticated tenant', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::ExpensesView]);
        $store = Store::factory()->create(['tenant_id' => $tenant->id]);
        $rent = ExpenseCategory::factory()->create([
            'tenant_id' => $tenant->id,
            'category_name' => 'Rent',
        ]);
        $supplies = ExpenseCategory::factory()->create([
            'tenant_id' => $tenant->id,
            'category_name' => 'Supplies',
        ]);
        $unused = ExpenseCategory::factory()->create([
            'tenant_id' => $tenant->id,
            'category_name' => 'Unused',
        ]);
        Expense::factory()->create([
            'tenant_id' => $tenant->id,
            'category_id' => $rent->id,
            'store_id' => $store->id,
            'amount' => '100.0000',
        ]);
        Expense::factory()->approved()->create([
            'tenant_id' => $tenant->id,
            'category_id' => $rent->id,
            'store_id' => $store->id,
            'amount' => '50.2500',
        ]);
        Expense::factory()->rejected()->create([
            'tenant_id' => $tenant->id,
            'category_id' => $supplies->id,
            'store_id' => $store->id,
            'amount' => '20.0000',
        ]);
        Expense::factory()->create();

        $response = $this->getJson('/api/v1/expenses/summary')
            ->assertOk()
            ->assertJsonPath('data.total_expenses', 3)
            ->assertJsonPath('data.total_amount', '170.2500')
            ->assertJsonPath('data.pending_count', 1)
            ->assertJsonPath('data.approved_count', 1)
            ->assertJsonPath('data.rejected_count', 1)
            ->assertJsonCount(3, 'data.categories');

        $categories = collect($response->json('data.categories'));

        expect($categories->firstWhere('id', $rent->id))->toMatchArray([
            'category_name' => 'Rent',
            'is_active' => true,
            'count' => 2,
            'amount' => '150.2500',
        ]);
        expect($categories->firstWhere('id', $supplies->id))->toMatchArray([
            'count' => 1,
            'amount' => '20.0000',
        ]);
        expect($categories->firstWhere('id', $unused->id))->toMatchArray([
            'count' => 0,
            'amount' => '0.0000',
        ]);
    });

    it('excludes expenses in stores the user is not allocated to', function () {
        $tenant = Tenant::factory()->create();
        $allowed = Store::factory()->create(['tenant_id' => $tenant->id]);
        $blocked = Store::factory()->create(['tenant_id' => $tenant->id]);
        $category = ExpenseCategory::factory()->create([
            'tenant_id' => $tenant->id,
            'category_name' => 'Utilities',
        ]);
        Expense::factory()->create([
            'tenant_id' => $tenant->id,
            'category_id' => $category->id,
            'store_id' => $allowed->id,
            'amount' => '10.0000',
        ]);
        Expense::factory()->create([
            'tenant_id' => $tenant->id,
            'category_id' => $category->id,
            'store_id' => $blocked->id,
            'amount' => '90.0000',
        ]);
        $cashier = User::factory()->forTenant($tenant)->withPermissions([
            Permission::ExpensesView,
        ])->create();
        $cashier->stores()->attach($allowed->id, ['tenant_id' => $tenant->id]);
        grantSubscriptionFeatures($tenant);
        Sanctum::actingAs($cashier);

        $this->getJson('/api/v1/expenses/summary')
            ->assertOk()
            ->assertJsonPath('data.total_expenses', 1)
            ->assertJsonPath('data.total_amount', '10.0000')
            ->assertJsonPath('data.categories.0.id', $category->id)
            ->assertJsonPath('data.categories.0.count', 1)
            ->assertJsonPath('data.categories.0.amount', '10.0000');
    });
});

describe('store', function () {
    it('creates a pending expense for the authenticated tenant', function () {
        $tenant = Tenant::factory()->create();
        $user = actingAsTenantUser($tenant, [Permission::ExpensesCreate]);
        $category = ExpenseCategory::factory()->create(['tenant_id' => $tenant->id]);
        $store = Store::factory()->create(['tenant_id' => $tenant->id]);
        $otherTenant = Tenant::factory()->create();

        $this->postJson('/api/v1/expenses', expensePayload([
            'category_id' => $category->id,
            'store_id' => $store->id,
            'status' => 'approved',
            'approval_time' => '2020-01-01 00:00:00',
            'tenant_id' => $otherTenant->id,
            'created_by' => 999,
        ]))
            ->assertCreated()
            ->assertJsonPath('data.description', 'October rent')
            ->assertJsonPath('data.amount', '1500.5000')
            ->assertJsonPath('data.expense_date', '2026-10-30')
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.approval_time', null)
            ->assertJsonPath('data.created_by', $user->id)
            ->assertJsonPath('data.updated_by', $user->id)
            ->assertJsonPath('data.category.id', $category->id)
            ->assertJsonPath('data.store.id', $store->id);

        $this->assertDatabaseHas('expenses', [
            'tenant_id' => $tenant->id,
            'category_id' => $category->id,
            'store_id' => $store->id,
            'amount' => '1500.5000',
            'status' => 'pending',
            'approval_time' => null,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
    });

    it('creates an approved expense when the user can approve', function () {
        $this->travelTo('2026-10-30 11:46:00');
        $tenant = Tenant::factory()->create();
        $user = actingAsTenantUser($tenant, [Permission::ExpensesCreate, Permission::ExpensesApprove]);
        $category = ExpenseCategory::factory()->create(['tenant_id' => $tenant->id]);
        $store = Store::factory()->create(['tenant_id' => $tenant->id]);

        $this->postJson('/api/v1/expenses', expensePayload([
            'category_id' => $category->id,
            'store_id' => $store->id,
            'status' => 'approved',
            'approval_time' => '2020-01-01 00:00:00',
        ]))
            ->assertCreated()
            ->assertJsonPath('data.status', 'approved')
            ->assertJsonPath('data.approval_time', '2026-10-30T11:46:00.000000Z')
            ->assertJsonPath('data.created_by', $user->id);

        $this->assertDatabaseHas('expenses', [
            'tenant_id' => $tenant->id,
            'status' => 'approved',
            'approval_time' => '2026-10-30 11:46:00',
        ]);
    });

    it('creates a rejected expense when the user can approve', function () {
        $this->travelTo('2026-10-30 11:46:00');
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::ExpensesCreate, Permission::ExpensesApprove]);
        $category = ExpenseCategory::factory()->create(['tenant_id' => $tenant->id]);
        $store = Store::factory()->create(['tenant_id' => $tenant->id]);

        $this->postJson('/api/v1/expenses', expensePayload([
            'category_id' => $category->id,
            'store_id' => $store->id,
            'status' => 'rejected',
        ]))
            ->assertCreated()
            ->assertJsonPath('data.status', 'rejected')
            ->assertJsonPath('data.approval_time', '2026-10-30T11:46:00.000000Z');
    });

    it('returns 422 when the create status is invalid', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::ExpensesCreate, Permission::ExpensesApprove]);
        $category = ExpenseCategory::factory()->create(['tenant_id' => $tenant->id]);
        $store = Store::factory()->create(['tenant_id' => $tenant->id]);

        $this->postJson('/api/v1/expenses', expensePayload([
            'category_id' => $category->id,
            'store_id' => $store->id,
            'status' => 'posted',
        ]))
            ->assertUnprocessable()
            ->assertJsonPath('errors.status.0', 'The selected status is invalid.');
    });

    it('stores an expense attachment', function () {
        Storage::fake('public');
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::ExpensesCreate]);
        $category = ExpenseCategory::factory()->create(['tenant_id' => $tenant->id]);
        $store = Store::factory()->create(['tenant_id' => $tenant->id]);
        $attachment = UploadedFile::fake()->image('receipt.jpg');

        $response = $this->post('/api/v1/expenses', expensePayload([
            'category_id' => $category->id,
            'store_id' => $store->id,
            'attachment' => $attachment,
        ]), ['Accept' => 'application/json']);

        $response->assertCreated();
        $path = $response->json('data.attachment');

        expect($path)->toBeString();
        Storage::disk('public')->assertExists($path);
    });

    it('returns 422 when required fields are missing', function () {
        actingAsTenantUser(permissions: [Permission::ExpensesCreate]);

        $this->postJson('/api/v1/expenses', [])
            ->assertUnprocessable()
            ->assertJsonPath('errors.category_id.0', 'The category id field is required.')
            ->assertJsonPath('errors.store_id.0', 'The store id field is required.')
            ->assertJsonPath('errors.amount.0', 'The amount field is required.')
            ->assertJsonPath('errors.expense_date.0', 'The expense date field is required.');
    });

    it('returns 422 when the amount is not greater than zero', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::ExpensesCreate]);
        $category = ExpenseCategory::factory()->create(['tenant_id' => $tenant->id]);
        $store = Store::factory()->create(['tenant_id' => $tenant->id]);

        $this->postJson('/api/v1/expenses', expensePayload([
            'category_id' => $category->id,
            'store_id' => $store->id,
            'amount' => '0',
        ]))->assertUnprocessable()
            ->assertJsonPath('errors.amount.0', 'The amount field must be greater than 0.');
    });

    it('returns 422 when the category is inactive', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::ExpensesCreate]);
        $category = ExpenseCategory::factory()->inactive()->create(['tenant_id' => $tenant->id]);
        $store = Store::factory()->create(['tenant_id' => $tenant->id]);

        $this->postJson('/api/v1/expenses', expensePayload([
            'category_id' => $category->id,
            'store_id' => $store->id,
        ]))->assertUnprocessable()
            ->assertJsonPath('errors.category_id.0', 'The selected category is invalid.');

        $this->assertDatabaseCount('expenses', 0);
    });

    it('returns 422 when the store belongs to another tenant', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::ExpensesCreate]);
        $category = ExpenseCategory::factory()->create(['tenant_id' => $tenant->id]);
        $foreignStore = Store::factory()->create();

        $this->postJson('/api/v1/expenses', expensePayload([
            'category_id' => $category->id,
            'store_id' => $foreignStore->id,
        ]))->assertUnprocessable()
            ->assertJsonPath('errors.store_id.0', 'The selected store is invalid.');

        $this->assertDatabaseCount('expenses', 0);
    });

    it('returns 422 when the store is not allocated to the user', function () {
        $tenant = Tenant::factory()->create();
        $allowed = Store::factory()->create(['tenant_id' => $tenant->id]);
        $blocked = Store::factory()->create(['tenant_id' => $tenant->id]);
        $category = ExpenseCategory::factory()->create(['tenant_id' => $tenant->id]);
        $cashier = User::factory()->forTenant($tenant)->withPermissions([
            Permission::ExpensesCreate,
        ])->create();
        $cashier->stores()->attach($allowed->id, ['tenant_id' => $tenant->id]);
        grantSubscriptionFeatures($tenant);
        Sanctum::actingAs($cashier);

        $this->postJson('/api/v1/expenses', expensePayload([
            'category_id' => $category->id,
            'store_id' => $blocked->id,
        ]))->assertUnprocessable()
            ->assertJsonPath('errors.store_id.0', 'The selected store is not allocated to you.');

        $this->assertDatabaseCount('expenses', 0);
    });

    it('returns 422 when the attachment type is not allowed', function () {
        Storage::fake('public');
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::ExpensesCreate]);
        $category = ExpenseCategory::factory()->create(['tenant_id' => $tenant->id]);
        $store = Store::factory()->create(['tenant_id' => $tenant->id]);

        $this->post('/api/v1/expenses', expensePayload([
            'category_id' => $category->id,
            'store_id' => $store->id,
            'attachment' => UploadedFile::fake()->create('notes.txt', 10, 'text/plain'),
        ]), ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.attachment.0', 'The attachment must be a file of type: jpg, jpeg, png, webp, pdf.');
    });
});

describe('update and destroy', function () {
    it('updates a pending expense without setting an approval time', function () {
        $tenant = Tenant::factory()->create();
        $user = actingAsTenantUser($tenant, [Permission::ExpensesUpdate]);
        $category = ExpenseCategory::factory()->create(['tenant_id' => $tenant->id]);
        $store = Store::factory()->create(['tenant_id' => $tenant->id]);
        $expense = Expense::factory()->create([
            'tenant_id' => $tenant->id,
            'category_id' => $category->id,
            'store_id' => $store->id,
            'description' => 'Old',
            'created_by' => $user->id,
        ]);

        $this->postJson('/api/v1/expenses/'.$expense->id, expensePayload([
            'category_id' => $category->id,
            'store_id' => $store->id,
            'description' => 'Revised rent',
            'amount' => '2000',
            'status' => 'approved',
            'approval_time' => '2020-01-01 00:00:00',
        ]))
            ->assertOk()
            ->assertJsonPath('data.description', 'Revised rent')
            ->assertJsonPath('data.amount', '2000.0000')
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.approval_time', null)
            ->assertJsonPath('data.updated_by', $user->id);

        $this->assertDatabaseHas('expenses', [
            'id' => $expense->id,
            'description' => 'Revised rent',
            'amount' => '2000.0000',
            'status' => 'pending',
            'approval_time' => null,
            'updated_by' => $user->id,
        ]);
    });

    it('returns 422 when updating an approved expense', function () {
        $tenant = Tenant::factory()->create();
        $user = actingAsTenantUser($tenant, [Permission::ExpensesUpdate]);
        $category = ExpenseCategory::factory()->create(['tenant_id' => $tenant->id]);
        $store = Store::factory()->create(['tenant_id' => $tenant->id]);
        $expense = Expense::factory()->approved()->create([
            'tenant_id' => $tenant->id,
            'category_id' => $category->id,
            'store_id' => $store->id,
            'description' => 'Paid',
            'created_by' => $user->id,
        ]);

        $this->postJson('/api/v1/expenses/'.$expense->id, expensePayload([
            'category_id' => $category->id,
            'store_id' => $store->id,
            'description' => 'Changed',
        ]))->assertUnprocessable()
            ->assertJsonPath('errors.expense.0', 'Only a pending expense can be updated.');

        $this->assertDatabaseHas('expenses', [
            'id' => $expense->id,
            'description' => 'Paid',
            'status' => 'approved',
        ]);
    });

    it('returns 404 when updating another tenants expense', function () {
        $tenantA = Tenant::factory()->create();
        actingAsTenantUser($tenantA, [Permission::ExpensesUpdate]);
        $category = ExpenseCategory::factory()->create(['tenant_id' => $tenantA->id]);
        $store = Store::factory()->create(['tenant_id' => $tenantA->id]);
        $foreign = Expense::factory()->create(['description' => 'Foreign bill']);

        $this->postJson('/api/v1/expenses/'.$foreign->id, expensePayload([
            'category_id' => $category->id,
            'store_id' => $store->id,
            'description' => 'Changed',
        ]))->assertNotFound();

        $this->assertDatabaseHas('expenses', [
            'id' => $foreign->id,
            'description' => 'Foreign bill',
        ]);
    });

    it('returns 422 when a non-creator deletes an expense', function () {
        $tenant = Tenant::factory()->create();
        $creator = User::factory()->forTenant($tenant)->create();
        $expense = Expense::factory()->create([
            'tenant_id' => $tenant->id,
            'created_by' => $creator->id,
        ]);
        actingAsTenantUser($tenant, [Permission::ExpensesDelete, Permission::OnlyCreatorDelete]);

        $this->postJson('/api/v1/expenses/delete', [
            'action' => 'delete',
            'ids' => [$expense->id],
        ])->assertUnprocessable()
            ->assertJsonPath('errors.expense.0', 'You are not authorized to delete this expense.');

        $this->assertModelExists($expense);
    });

    it('deletes a pending expense and its attachment', function () {
        Storage::fake('public');
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::ExpensesCreate, Permission::ExpensesDelete]);
        $category = ExpenseCategory::factory()->create(['tenant_id' => $tenant->id]);
        $store = Store::factory()->create(['tenant_id' => $tenant->id]);
        $attachment = UploadedFile::fake()->image('receipt.jpg');

        $created = $this->post('/api/v1/expenses', expensePayload([
            'category_id' => $category->id,
            'store_id' => $store->id,
            'attachment' => $attachment,
        ]), ['Accept' => 'application/json'])->assertCreated();

        $path = $created->json('data.attachment');
        $id = $created->json('data.id');

        $this->postJson('/api/v1/expenses/delete', [
            'action' => 'delete',
            'ids' => [$id],
        ])->assertOk()
            ->assertJsonPath('message', 'Expense deleted successfully');

        $this->assertDatabaseMissing('expenses', ['id' => $id]);
        Storage::disk('public')->assertMissing($path);
    });

    it('rejects deleting an approved expense', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::ExpensesDelete]);
        $expense = Expense::factory()->approved()->create(['tenant_id' => $tenant->id]);

        $this->postJson('/api/v1/expenses/delete', [
            'action' => 'delete',
            'ids' => [$expense->id],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors([
                'ids' => 'Only a pending expense can be deleted.',
            ]);

        $this->assertModelExists($expense);
    });

    it('rejects a delete request with an invalid action', function () {
        actingAsTenantUser(permissions: [Permission::ExpensesDelete]);

        $this->postJson('/api/v1/expenses/delete', [
            'action' => 'archive',
            'ids' => [1],
        ])->assertBadRequest();
    });
});

describe('approval', function () {
    it('approves a pending expense and records the approval time', function () {
        $this->travelTo('2026-10-30 11:46:00');
        $tenant = Tenant::factory()->create();
        $user = actingAsTenantUser($tenant, [Permission::ExpensesApprove]);
        $expense = Expense::factory()->create(['tenant_id' => $tenant->id]);

        $this->putJson('/api/v1/expenses/'.$expense->id.'/status', [
            'status' => 'approved',
        ])
            ->assertOk()
            ->assertJsonPath('data.status', 'approved')
            ->assertJsonPath('data.approval_time', '2026-10-30T11:46:00.000000Z')
            ->assertJsonPath('data.updated_by', $user->id);

        $this->assertDatabaseHas('expenses', [
            'id' => $expense->id,
            'status' => 'approved',
            'approval_time' => '2026-10-30 11:46:00',
            'updated_by' => $user->id,
        ]);
    });

    it('rejects a pending expense and records the approval time', function () {
        $this->travelTo('2026-10-30 11:46:00');
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::ExpensesApprove]);
        $expense = Expense::factory()->create(['tenant_id' => $tenant->id]);

        $this->putJson('/api/v1/expenses/'.$expense->id.'/status', [
            'status' => 'rejected',
        ])
            ->assertOk()
            ->assertJsonPath('data.status', 'rejected')
            ->assertJsonPath('data.approval_time', '2026-10-30T11:46:00.000000Z');

        $this->assertDatabaseHas('expenses', [
            'id' => $expense->id,
            'status' => 'rejected',
            'approval_time' => '2026-10-30 11:46:00',
        ]);
    });

    it('returns 422 when approving an expense that is no longer pending', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::ExpensesApprove]);
        $expense = Expense::factory()->approved()->create(['tenant_id' => $tenant->id]);

        $this->putJson('/api/v1/expenses/'.$expense->id.'/status', [
            'status' => 'rejected',
        ])->assertUnprocessable()
            ->assertJsonPath('errors.status.0', 'Only a pending expense can be approved or rejected.');

        $this->assertDatabaseHas('expenses', [
            'id' => $expense->id,
            'status' => 'approved',
        ]);
    });

    it('returns 422 when the status is pending', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::ExpensesApprove]);
        $expense = Expense::factory()->create(['tenant_id' => $tenant->id]);

        $this->putJson('/api/v1/expenses/'.$expense->id.'/status', [
            'status' => 'pending',
        ])->assertUnprocessable()
            ->assertJsonPath('errors.status.0', 'The selected status is invalid.');

        $this->assertDatabaseHas('expenses', [
            'id' => $expense->id,
            'status' => 'pending',
            'approval_time' => null,
        ]);
    });

    it('returns 404 when approving another tenants expense', function () {
        $tenantA = Tenant::factory()->create();
        actingAsTenantUser($tenantA, [Permission::ExpensesApprove]);
        $foreign = Expense::factory()->create();

        $this->putJson('/api/v1/expenses/'.$foreign->id.'/status', [
            'status' => 'approved',
        ])->assertNotFound();
    });
});
