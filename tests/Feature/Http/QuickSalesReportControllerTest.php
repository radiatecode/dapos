<?php

use App\Enums\Permission;
use Barryvdh\Snappy\Facades\SnappyPdf;
use DA\Admin\Models\Tenant;
use DA\Inventory\Enums\PaymentMethod;
use DA\Inventory\Models\QuickSale;
use DA\Inventory\Supports\Reports\QuickSalesReportExcelExport;
use Illuminate\Database\Eloquent\Model;
use Maatwebsite\Excel\Facades\Excel;

describe('authentication and authorization', function () {
    it('returns 401 when no token is provided', function (string $uri) {
        $this->getJson($uri)->assertUnauthorized();
    })->with([
        '/api/v1/reports/quick-sales',
        '/api/v1/reports/quick-sales/summary',
        '/api/v1/reports/quick-sales/export/pdf',
        '/api/v1/reports/quick-sales/export/excel',
    ]);

    it('returns 403 when the user cannot view the quick sales report', function () {
        actingAsTenantUser(permissions: [Permission::QuickSalesView]);

        $this->getJson('/api/v1/reports/quick-sales')->assertForbidden();
    });
});

describe('with recorded quick sales', function () {
    beforeEach(function () {
        $user = actingAsTenantUser(permissions: [Permission::QuickSalesReportsView]);
        $tenantId = (int) $user->tenant_id;

        foreach ([
            ['2026-10-01', 2, '300', PaymentMethod::CASH],
            ['2026-10-01', 1, '100', PaymentMethod::CARD],
            ['2026-10-05', 3, '50', PaymentMethod::MOBILE_BANKING],
        ] as [$date, $lines, $total, $method]) {
            QuickSale::factory()->create([
                'tenant_id' => $tenantId,
                'sale_date' => $date,
                'total_items' => $lines,
                'grand_total' => $total,
                'payment_method' => $method,
            ]);
        }

        Model::withoutEvents(fn () => QuickSale::factory()->create([
            'tenant_id' => Tenant::factory()->create()->id,
            'sale_date' => '2026-10-01',
            'grand_total' => '9000',
        ]));
    });

    it('groups the tenant quick sales by day, newest first, split by payment method', function () {
        $this->getJson('/api/v1/reports/quick-sales')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0', [
                'sale_date' => '2026-10-05',
                'quick_sales_count' => 1,
                'total_items' => 3,
                'total_amount' => '50.0000',
                'cash_amount' => '0.0000',
                'card_amount' => '0.0000',
                'bank_transfer_amount' => '0.0000',
                'mobile_banking_amount' => '50.0000',
            ])
            ->assertJsonPath('data.1', [
                'sale_date' => '2026-10-01',
                'quick_sales_count' => 2,
                'total_items' => 3,
                'total_amount' => '400.0000',
                'cash_amount' => '300.0000',
                'card_amount' => '100.0000',
                'bank_transfer_amount' => '0.0000',
                'mobile_banking_amount' => '0.0000',
            ]);
    });

    it('totals the quick sales within the selected period', function () {
        $this->getJson('/api/v1/reports/quick-sales/summary?date_from=2026-10-01&date_to=2026-10-01')
            ->assertOk()
            ->assertExactJson(['data' => [
                'total_quick_sales' => 2,
                'total_items' => 3,
                'total_amount' => '400.0000',
                'cash_amount' => '300.0000',
                'card_amount' => '100.0000',
                'bank_transfer_amount' => '0.0000',
                'mobile_banking_amount' => '0.0000',
                'average_amount' => '200.0000',
            ]]);
    });

    it('rejects an end date before the start date', function () {
        $this->getJson('/api/v1/reports/quick-sales?date_from=2026-10-05&date_to=2026-10-01')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['date_to']);
    });

    it('downloads the daily quick sales as a pdf', function () {
        SnappyPdf::fake();

        $this->get('/api/v1/reports/quick-sales/export/pdf')
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        SnappyPdf::assertViewIs('inventory::reports.quick-sales');
        SnappyPdf::assertSeeText('01 Oct 2026');
        SnappyPdf::assertSeeText('450.00');
        SnappyPdf::assertDontSeeText('9,000.00');
    });

    it('downloads only the cash quick sales as an excel sheet', function () {
        Excel::fake();
        $this->travelTo('2026-10-13 09:15:00');

        $this->get('/api/v1/reports/quick-sales/export/excel?payment_method='.PaymentMethod::CASH->value)->assertOk();

        Excel::assertDownloaded('quick-sales-report-2026-10-13-091500.xlsx', function (QuickSalesReportExcelExport $export): bool {
            $sheet = $export->array();

            return in_array(['Payment', 'Cash'], $sheet, true)
                && in_array(['Quick sales', 1], $sheet, true)
                && in_array(['Total amount', 300.0], $sheet, true)
                && in_array([1, '01 Oct 2026', 1, 2, 300.0, 0.0, 0.0, 0.0, 300.0], $sheet, true)
                && end($sheet)[0] === 'Total';
        });
    });
});
