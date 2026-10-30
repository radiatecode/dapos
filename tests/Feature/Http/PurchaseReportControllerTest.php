<?php

use App\Enums\Permission;
use App\Models\User;
use Barryvdh\Snappy\Facades\SnappyPdf;
use DA\Admin\Enums\Features;
use DA\Admin\Models\Tenant;
use DA\Inventory\Enums\PurchasePaymentStatus;
use DA\Inventory\Models\Purchase;
use DA\Inventory\Models\Store;
use DA\Inventory\Models\Supplier;
use DA\Inventory\Supports\Reports\PurchaseReportExcelExport;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\Sanctum;
use Maatwebsite\Excel\Facades\Excel;

function allowPurchaseReport(User $user): User
{
    $user->unsetRelation('tenant');
    $subscription = $user->tenant->subscription;
    $features = collect($subscription->features ?? [])
        ->map(fn (mixed $feature): string => $feature instanceof BackedEnum ? $feature->value : (string) $feature)
        ->push(Features::PURCHASE_REPORT->value)
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
        '/api/v1/reports/purchases',
        '/api/v1/reports/purchases/summary',
        '/api/v1/reports/purchases/export/pdf',
        '/api/v1/reports/purchases/export/excel',
    ]);

    it('returns 403 when the user cannot view purchase reports', function () {
        actingAsTenantUser(permissions: [Permission::PurchasingView]);

        $this->getJson('/api/v1/reports/purchases')->assertForbidden();
    });

    it('returns 403 when the subscription does not include the purchase report', function () {
        actingAsTenantUser(permissions: [Permission::PurchasesReportsView]);

        $this->getJson('/api/v1/reports/purchases')
            ->assertForbidden()
            ->assertJsonPath('message', 'This feature is not available on the current subscription.');
    });
});

describe('with recorded purchases', function () {
    beforeEach(function () {
        $this->user = allowPurchaseReport(actingAsTenantUser(permissions: [Permission::PurchasesReportsView]));
        $tenantId = (int) $this->user->tenant_id;

        $this->store = Store::factory()->create(['tenant_id' => $tenantId, 'name' => 'Main']);
        $this->supplier = Supplier::factory()->create([
            'tenant_id' => $tenantId,
            'name' => 'Acme Supplies',
        ]);

        Purchase::factory()->create([
            'tenant_id' => $tenantId,
            'store_id' => $this->store->id,
            'supplier_id' => $this->supplier->id,
            'po_number' => 'PO-PAID',
            'order_date' => '2026-10-20',
            'grand_total' => '100.0000',
            'paid_amount' => '100.0000',
            'due_amount' => '0.0000',
            'payment_status' => PurchasePaymentStatus::Paid,
        ]);
        Purchase::factory()->create([
            'tenant_id' => $tenantId,
            'store_id' => $this->store->id,
            'supplier_id' => $this->supplier->id,
            'po_number' => 'PO-DUE',
            'order_date' => '2026-10-05',
            'grand_total' => '40.0000',
            'paid_amount' => '0.0000',
            'due_amount' => '40.0000',
            'payment_status' => PurchasePaymentStatus::Unpaid,
        ]);

        Model::withoutEvents(function (): void {
            $otherTenant = Tenant::factory()->create();

            Purchase::factory()->create([
                'tenant_id' => $otherTenant->id,
                'po_number' => 'PO-FOREIGN',
                'grand_total' => '500.0000',
                'paid_amount' => '500.0000',
                'due_amount' => '0.0000',
                'payment_status' => PurchasePaymentStatus::Paid,
            ]);
        });
    });

    it('lists matching purchases and keeps payment totals when a payment filter is applied', function () {
        $this->getJson('/api/v1/reports/purchases?payment_status=Paid')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.po_number', 'PO-PAID')
            ->assertJsonPath('data.0.payment_status', 'Paid')
            ->assertJsonPath('data.0.supplier.name', 'Acme Supplies')
            ->assertJsonPath('data.0.store.name', 'Main')
            ->assertJsonMissing(['po_number' => 'PO-FOREIGN']);

        $this->getJson('/api/v1/reports/purchases/summary?payment_status=Paid')
            ->assertOk()
            ->assertJsonPath('data.total_purchases', 2)
            ->assertJsonPath('data.total_amount', '140.0000')
            ->assertJsonPath('data.total_paid', '100.0000')
            ->assertJsonPath('data.total_due', '40.0000')
            ->assertJsonPath('data.paid_count', 1)
            ->assertJsonPath('data.unpaid_count', 1)
            ->assertJsonPath('data.partial_count', 0);
    });

    it('returns 422 when the payment filter is invalid', function () {
        $this->getJson('/api/v1/reports/purchases?payment_status=Later')
            ->assertUnprocessable()
            ->assertJsonPath('errors.payment_status.0', 'The selected payment status is invalid.');
    });

    it('excludes purchases in stores the user is not allocated to', function () {
        $blocked = Store::factory()->create(['tenant_id' => $this->user->tenant_id, 'name' => 'Blocked']);
        Purchase::factory()->create([
            'tenant_id' => $this->user->tenant_id,
            'supplier_id' => $this->supplier->id,
            'store_id' => $blocked->id,
            'po_number' => 'PO-HIDDEN',
            'grand_total' => '90.0000',
        ]);
        $cashier = User::factory()->forTenant($this->user->tenant)->withPermissions([
            Permission::PurchasesReportsView,
        ])->create();
        $cashier->stores()->attach($this->store->id, ['tenant_id' => $this->user->tenant_id]);
        Sanctum::actingAs(allowPurchaseReport($cashier));

        $this->getJson('/api/v1/reports/purchases')
            ->assertOk()
            ->assertJsonMissing(['po_number' => 'PO-HIDDEN']);

        $this->getJson('/api/v1/reports/purchases/summary')
            ->assertOk()
            ->assertJsonPath('data.total_purchases', 2)
            ->assertJsonPath('data.total_amount', '140.0000');
    });

    it('downloads the purchases and the summary as a pdf', function () {
        SnappyPdf::fake();

        $this->get('/api/v1/reports/purchases/export/pdf')
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        SnappyPdf::assertViewIs('inventory::reports.purchases');
        SnappyPdf::assertSeeText('PO-PAID');
        SnappyPdf::assertSeeText('PO-DUE');
        SnappyPdf::assertSeeText('140.00');
        SnappyPdf::assertSeeText('100.00');
        SnappyPdf::assertSeeText('71.4% collected');
        SnappyPdf::assertSeeText('1 purchase outstanding');
        SnappyPdf::assertDontSeeText('PO-FOREIGN');
    });

    it('downloads the filtered rows and the unfiltered payment totals as excel', function () {
        Excel::fake();
        $this->travelTo('2026-10-30 17:50:00');

        $this->get('/api/v1/reports/purchases/export/excel?payment_status=Unpaid')
            ->assertOk();

        Excel::assertDownloaded('purchase-report-2026-10-30-175000.xlsx', function (PurchaseReportExcelExport $export): bool {
            $sheet = $export->array();
            $rows = collect($sheet)->filter(fn (array $row): bool => in_array($row[1] ?? null, ['PO-PAID', 'PO-DUE'], true));

            return $rows->count() === 1
                && $rows->first()[1] === 'PO-DUE'
                && $rows->first()[3] === 'Acme Supplies'
                && $rows->first()[9] === 'Unpaid'
                && in_array(['Total purchases', 2], $sheet, true)
                && in_array(['Total purchase amount', 140.0], $sheet, true)
                && in_array(['Total paid', 100.0], $sheet, true)
                && in_array(['Total due', 40.0], $sheet, true)
                && in_array(['Collected', '71.4%'], $sheet, true)
                && in_array(['Purchases outstanding', 1], $sheet, true)
                && in_array(['Payment', 'Unpaid'], $sheet, true)
                && end($sheet)[0] === 'Total'
                && end($sheet)[5] === 40.0;
        });
    });
});
