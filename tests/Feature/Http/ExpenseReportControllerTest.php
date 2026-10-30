<?php

use App\Enums\Permission;
use App\Models\User;
use Barryvdh\Snappy\Facades\SnappyPdf;
use DA\Admin\Enums\Features;
use DA\Admin\Models\Tenant;
use DA\Expense\Models\Expense;
use DA\Expense\Models\ExpenseCategory;
use DA\Expense\Supports\Reports\ExpenseReportExcelExport;
use DA\Inventory\Models\Store;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\Sanctum;
use Maatwebsite\Excel\Facades\Excel;

function allowExpenseReport(User $user): User
{
    $user->unsetRelation('tenant');
    $subscription = $user->tenant->subscription;
    $features = collect($subscription->features ?? [])
        ->map(fn (mixed $feature): string => $feature instanceof BackedEnum ? $feature->value : (string) $feature)
        ->push(Features::EXPENSE_REPORT->value)
        ->unique()
        ->values()
        ->all();
    $subscription->features = $features;
    $subscription->save();

    return $user;
}

describe('authentication and authorization', function () {
    it('returns 401 when no token is provided', function (string $uri) {
        $this->getJson($uri)->assertUnauthorized();
    })->with([
        '/api/v1/reports/expenses',
        '/api/v1/reports/expenses/summary',
        '/api/v1/reports/expenses/export/pdf',
        '/api/v1/reports/expenses/export/excel',
    ]);

    it('returns 403 when the user cannot view expense reports', function () {
        actingAsTenantUser(permissions: [Permission::ExpensesView]);

        $this->getJson('/api/v1/reports/expenses')->assertForbidden();
    });

    it('returns 403 when the subscription does not include the expense report', function () {
        actingAsTenantUser(permissions: [Permission::ExpensesReportsView]);

        $this->getJson('/api/v1/reports/expenses')
            ->assertForbidden()
            ->assertJsonPath('message', 'This feature is not available on the current subscription.');
    });
});

describe('with recorded expenses', function () {
    beforeEach(function () {
        $this->user = allowExpenseReport(actingAsTenantUser(permissions: [Permission::ExpensesReportsView]));
        $tenantId = (int) $this->user->tenant_id;

        $this->store = Store::factory()->create(['tenant_id' => $tenantId, 'name' => 'Main']);
        $this->rent = ExpenseCategory::factory()->create([
            'tenant_id' => $tenantId,
            'category_name' => 'Rent',
        ]);
        $this->supplies = ExpenseCategory::factory()->create([
            'tenant_id' => $tenantId,
            'category_name' => 'Supplies',
        ]);

        Expense::factory()->approved()->create([
            'tenant_id' => $tenantId,
            'category_id' => $this->rent->id,
            'store_id' => $this->store->id,
            'description' => 'October rent',
            'amount' => '100.0000',
            'expense_date' => '2026-10-20',
        ]);
        Expense::factory()->create([
            'tenant_id' => $tenantId,
            'category_id' => $this->supplies->id,
            'store_id' => $this->store->id,
            'description' => 'Printer paper',
            'amount' => '40.0000',
            'expense_date' => '2026-10-05',
        ]);

        Model::withoutEvents(function (): void {
            $otherTenant = Tenant::factory()->create();

            Expense::factory()->create([
                'tenant_id' => $otherTenant->id,
                'description' => 'Foreign bill',
                'amount' => '500.0000',
                'expense_date' => '2026-10-01',
            ]);
        });
    });

    it('lists matching expenses and keeps status totals when a status filter is applied', function () {
        $this->getJson('/api/v1/reports/expenses?status=approved')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.description', 'October rent')
            ->assertJsonPath('data.0.status', 'approved')
            ->assertJsonPath('data.0.category.category_name', 'Rent')
            ->assertJsonPath('data.0.store.name', 'Main')
            ->assertJsonMissing(['description' => 'Foreign bill']);

        $this->getJson('/api/v1/reports/expenses/summary?status=approved')
            ->assertOk()
            ->assertJsonPath('data.total_expenses', 2)
            ->assertJsonPath('data.total_amount', '140.0000')
            ->assertJsonPath('data.approved_count', 1)
            ->assertJsonPath('data.approved_amount', '100.0000')
            ->assertJsonPath('data.pending_count', 1)
            ->assertJsonPath('data.pending_amount', '40.0000')
            ->assertJsonPath('data.rejected_count', 0);
    });

    it('returns 422 when the status filter is invalid', function () {
        $this->getJson('/api/v1/reports/expenses?status=posted')
            ->assertUnprocessable()
            ->assertJsonPath('errors.status.0', 'The selected status is invalid.');
    });

    it('excludes expenses in stores the user is not allocated to', function () {
        $blocked = Store::factory()->create(['tenant_id' => $this->user->tenant_id, 'name' => 'Blocked']);
        Expense::factory()->create([
            'tenant_id' => $this->user->tenant_id,
            'category_id' => $this->rent->id,
            'store_id' => $blocked->id,
            'description' => 'Hidden bill',
            'amount' => '90.0000',
        ]);
        $cashier = User::factory()->forTenant($this->user->tenant)->withPermissions([
            Permission::ExpensesReportsView,
        ])->create();
        $cashier->stores()->attach($this->store->id, ['tenant_id' => $this->user->tenant_id]);
        Sanctum::actingAs(allowExpenseReport($cashier));

        $this->getJson('/api/v1/reports/expenses')
            ->assertOk()
            ->assertJsonMissing(['description' => 'Hidden bill']);

        $this->getJson('/api/v1/reports/expenses/summary')
            ->assertOk()
            ->assertJsonPath('data.total_expenses', 2)
            ->assertJsonPath('data.total_amount', '140.0000');
    });

    it('downloads the expenses and the summary as a pdf', function () {
        SnappyPdf::fake();

        $this->get('/api/v1/reports/expenses/export/pdf')
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        SnappyPdf::assertViewIs('expense::reports.expenses');
        SnappyPdf::assertSeeText('October rent');
        SnappyPdf::assertSeeText('Printer paper');
        SnappyPdf::assertSeeText('140.00');
        SnappyPdf::assertSeeText('100.00');
        SnappyPdf::assertDontSeeText('Foreign bill');
    });

    it('downloads the filtered rows and the unfiltered status totals as excel', function () {
        Excel::fake();
        $this->travelTo('2026-10-30 14:05:00');

        $this->get('/api/v1/reports/expenses/export/excel?status=approved')
            ->assertOk();

        Excel::assertDownloaded('expense-report-2026-10-30-140500.xlsx', function (ExpenseReportExcelExport $export): bool {
            $sheet = $export->array();
            $rows = collect($sheet)->filter(fn (array $row): bool => in_array($row[1] ?? null, ['October rent', 'Printer paper'], true));

            return $rows->count() === 1
                && $rows->first()[1] === 'October rent'
                && $rows->first()[2] === 'Rent'
                && $rows->first()[6] === 'Approved'
                && in_array(['Expenses', 2], $sheet, true)
                && in_array(['Total amount', 140.0], $sheet, true)
                && in_array(['Approved amount', 100.0], $sheet, true)
                && in_array(['Pending amount', 40.0], $sheet, true)
                && in_array(['Status', 'Approved'], $sheet, true)
                && end($sheet)[0] === 'Total'
                && end($sheet)[5] === 100.0;
        });
    });
});
