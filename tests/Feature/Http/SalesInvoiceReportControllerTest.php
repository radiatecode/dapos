<?php

use App\Enums\Permission;
use Barryvdh\Snappy\Facades\SnappyPdf;
use DA\Admin\Models\Tenant;
use DA\Inventory\Enums\SalePaymentStatus;
use DA\Inventory\Models\Customer;
use DA\Inventory\Models\Sale;
use DA\Inventory\Models\Store;
use DA\Inventory\Supports\Reports\SalesInvoiceReportExcelExport;
use Illuminate\Database\Eloquent\Model;
use Maatwebsite\Excel\Facades\Excel;

/**
 * @param  array<string, mixed>  $attributes
 */
function salesInvoice(int $tenantId, array $attributes): Sale
{
    return Sale::factory()->create([...$attributes, 'tenant_id' => $tenantId]);
}

describe('authentication and authorization', function () {
    it('returns 401 when no token is provided', function (string $uri) {
        $this->getJson($uri)->assertUnauthorized();
    })->with([
        '/api/v1/reports/sales-invoices/export/pdf',
        '/api/v1/reports/sales-invoices/export/excel',
    ]);

    it('returns 403 when the user cannot view reports', function () {
        actingAsTenantUser(permissions: [Permission::SalesView]);

        $this->getJson('/api/v1/reports/sales-invoices/export/pdf')->assertForbidden();
    });
});

describe('with recorded sales', function () {
    beforeEach(function () {
        $this->user = actingAsTenantUser(permissions: [Permission::ReportsView]);
        $tenantId = (int) $this->user->tenant_id;

        $this->mainStore = Store::factory()->create(['tenant_id' => $tenantId, 'name' => 'Main']);
        $this->customer = Customer::factory()->create(['tenant_id' => $tenantId, 'name' => 'Ada']);

        salesInvoice($tenantId, [
            'store_id' => $this->mainStore->id,
            'customer_id' => $this->customer->id,
            'invoice_number' => 'INV-PAID',
            'sale_date' => '2026-10-01',
            'total_items' => '2',
            'grand_total' => '20',
            'paid_amount' => '20',
            'due_amount' => '0',
            'payment_status' => SalePaymentStatus::Paid,
        ]);

        salesInvoice($tenantId, [
            'store_id' => $this->mainStore->id,
            'customer_id' => null,
            'invoice_number' => 'INV-DUE',
            'sale_date' => '2026-10-05',
            'total_items' => '1',
            'grand_total' => '10',
            'paid_amount' => '0',
            'due_amount' => '10',
            'payment_status' => SalePaymentStatus::Unpaid,
        ]);

        Model::withoutEvents(function (): void {
            $otherTenant = Tenant::factory()->create();

            salesInvoice($otherTenant->id, [
                'invoice_number' => 'INV-FOREIGN',
                'sale_date' => '2026-10-01',
                'grand_total' => '500',
                'paid_amount' => '500',
                'due_amount' => '0',
                'payment_status' => SalePaymentStatus::Paid,
            ]);
        });
    });

    it('downloads the invoices and the sales summary as a pdf', function () {
        SnappyPdf::fake();

        $this->get('/api/v1/reports/sales-invoices/export/pdf')
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        SnappyPdf::assertViewIs('inventory::reports.sales-invoices');
        SnappyPdf::assertSeeText('INV-PAID');
        SnappyPdf::assertSeeText('INV-DUE');
        SnappyPdf::assertSeeText('Walk-in customer');
        SnappyPdf::assertSeeText('30.00');
        SnappyPdf::assertSeeText('66.7% collected');
        SnappyPdf::assertSeeText('1 invoice outstanding');
        SnappyPdf::assertDontSeeText('INV-FOREIGN');
    });

    it('keeps the summary totals when the invoice list is limited to unpaid sales', function () {
        Excel::fake();
        $this->travelTo('2026-10-13 09:15:00');

        $this->get('/api/v1/reports/sales-invoices/export/excel?payment_status=Unpaid')->assertOk();

        Excel::assertDownloaded('sales-invoice-report-2026-10-13-091500.xlsx', function (SalesInvoiceReportExcelExport $export): bool {
            $sheet = $export->array();
            $invoices = collect($sheet)->filter(fn (array $row): bool => in_array($row[1] ?? null, ['INV-PAID', 'INV-DUE'], true));

            return $invoices->count() === 1
                && $invoices->first()[1] === 'INV-DUE'
                && in_array(['Total sales', 2], $sheet, true)
                && in_array(['Fully paid', 1], $sheet, true)
                && in_array(['Total sale amount', 30.0], $sheet, true)
                && in_array(['Total paid', 20.0], $sheet, true)
                && in_array(['Collected', '66.7%'], $sheet, true)
                && in_array(['Total due', 10.0], $sheet, true)
                && in_array(['Invoices outstanding', 1], $sheet, true)
                && in_array(['Payment', 'Unpaid'], $sheet, true)
                && end($sheet)[0] === 'Total'
                && end($sheet)[6] === 10.0;
        });
    });

    it('rejects an end date before the start date and an unknown payment status', function () {
        $this->getJson('/api/v1/reports/sales-invoices/export/excel?date_from=2026-10-05&date_to=2026-10-01&payment_status=Later')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['date_to', 'payment_status']);
    });
});
