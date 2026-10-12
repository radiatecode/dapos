<?php

use App\Enums\Permission;
use Barryvdh\Snappy\Facades\SnappyPdf;
use DA\Admin\Models\Tenant;
use DA\Inventory\Models\Brand;
use DA\Inventory\Models\Category;
use DA\Inventory\Models\Product;
use DA\Inventory\Models\ProductVariant;
use DA\Inventory\Models\Sale;
use DA\Inventory\Models\SaleItem;
use DA\Inventory\Models\Store;
use DA\Inventory\Supports\Reports\SalesReportExcelExport;
use Illuminate\Database\Eloquent\Model;
use Maatwebsite\Excel\Facades\Excel;

function salesReportVariant(int $tenantId, Brand $brand, Category $category, string $sku, string $name): ProductVariant
{
    $product = Product::factory()->create([
        'tenant_id' => $tenantId,
        'brand_id' => $brand->id,
        'category_id' => $category->id,
        'name' => $name,
    ]);

    return ProductVariant::factory()->create([
        'tenant_id' => $tenantId,
        'product_id' => $product->id,
        'sku' => $sku,
    ]);
}

/**
 * @param  list<array{0: ProductVariant, 1: string, 2: string}>  $lines  variant, quantity, line total
 * @param  array<string, mixed>  $attributes
 */
function salesReportSale(int $tenantId, array $lines, array $attributes): Sale
{
    $sale = Sale::factory()->create([...$attributes, 'tenant_id' => $tenantId]);

    foreach ($lines as [$variant, $quantity, $total]) {
        SaleItem::factory()->create([
            'tenant_id' => $tenantId,
            'sale_id' => $sale->id,
            'product_variant_id' => $variant->id,
            'product_id' => $variant->product_id,
            'quantity' => $quantity,
            'subtotal' => $total,
            'total' => $total,
        ]);
    }

    return $sale;
}

describe('authentication and authorization', function () {
    it('returns 401 when no token is provided', function (string $uri) {
        $this->getJson($uri)->assertUnauthorized();
    })->with([
        '/api/v1/reports/sales',
        '/api/v1/reports/sales/summary',
        '/api/v1/reports/sales/export/pdf',
        '/api/v1/reports/sales/export/excel',
    ]);

    it('returns 403 when the user cannot view reports', function () {
        actingAsTenantUser(permissions: [Permission::SalesView]);

        $this->getJson('/api/v1/reports/sales')->assertForbidden();
    });
});

describe('with recorded sales', function () {
    beforeEach(function () {
        $this->user = actingAsTenantUser(permissions: [Permission::ReportsView]);
        $tenantId = (int) $this->user->tenant_id;

        $this->brandAcme = Brand::factory()->create(['tenant_id' => $tenantId, 'name' => 'Acme']);
        $this->brandOther = Brand::factory()->create(['tenant_id' => $tenantId, 'name' => 'Other']);
        $this->parentCategory = Category::factory()->create(['tenant_id' => $tenantId, 'name' => 'Drinks']);
        $this->childCategory = Category::factory()->childOf($this->parentCategory)->create(['name' => 'Soda']);
        $this->snackCategory = Category::factory()->create(['tenant_id' => $tenantId, 'name' => 'Snacks']);
        $this->mainStore = Store::factory()->create(['tenant_id' => $tenantId]);
        $this->branchStore = Store::factory()->create(['tenant_id' => $tenantId]);

        $this->cola = salesReportVariant($tenantId, $this->brandAcme, $this->childCategory, 'COLA-1', 'Cola');
        $this->chips = salesReportVariant($tenantId, $this->brandOther, $this->snackCategory, 'CHIPS-1', 'Chips');

        // Lines total 30, invoice discount brings the grand total to 27: cola gets 18, chips gets 9.
        salesReportSale($tenantId, [[$this->cola, '2', '20'], [$this->chips, '1', '10']], [
            'store_id' => $this->mainStore->id,
            'sale_date' => '2026-10-01',
            'discount_amount' => '3',
            'grand_total' => '27',
            'paid_amount' => '27',
            'due_amount' => '0',
        ]);

        salesReportSale($tenantId, [[$this->cola, '1', '10']], [
            'store_id' => $this->branchStore->id,
            'sale_date' => '2026-10-05',
            'grand_total' => '10',
            'paid_amount' => '4',
            'due_amount' => '6',
        ]);

        Model::withoutEvents(function (): void {
            $otherTenant = Tenant::factory()->create();
            $otherVariant = salesReportVariant(
                $otherTenant->id,
                Brand::factory()->create(['tenant_id' => $otherTenant->id]),
                Category::factory()->create(['tenant_id' => $otherTenant->id]),
                'FOREIGN-1',
                'Foreign product',
            );

            salesReportSale($otherTenant->id, [[$otherVariant, '50', '500']], [
                'sale_date' => '2026-10-01',
                'grand_total' => '500',
                'paid_amount' => '500',
            ]);
        });
    });

    describe('report rows', function () {
        it('aggregates sold quantity, sold amount, and paid amount per sku for the current tenant only', function () {
            $this->getJson('/api/v1/reports/sales')
                ->assertOk()
                ->assertJsonCount(2, 'data')
                ->assertJsonPath('meta.total', 2)
                ->assertJsonPath('data.0', [
                    'product_variant_id' => $this->cola->id,
                    'sku' => 'COLA-1',
                    'product_name' => 'Cola',
                    'variant_name' => null,
                    'brand_name' => 'Acme',
                    'category_name' => 'Soda',
                    'sold_quantity' => '3.0000',
                    'sold_amount' => '28.0000',
                    'paid_amount' => '22.0000',
                ])
                ->assertJsonPath('data.1.sku', 'CHIPS-1')
                ->assertJsonPath('data.1.sold_amount', '9.0000')
                ->assertJsonPath('data.1.paid_amount', '9.0000');
        });

        it('keeps the invoice share of each line when filtering by brand', function () {
            $this->getJson('/api/v1/reports/sales?brand_id='.$this->brandAcme->id)
                ->assertOk()
                ->assertJsonCount(1, 'data')
                ->assertJsonPath('data.0.sku', 'COLA-1')
                ->assertJsonPath('data.0.sold_amount', '28.0000')
                ->assertJsonPath('data.0.paid_amount', '22.0000');
        });

        it('includes products from child categories when filtering by a parent category', function () {
            $this->getJson('/api/v1/reports/sales?category_id='.$this->parentCategory->id)
                ->assertOk()
                ->assertJsonCount(1, 'data')
                ->assertJsonPath('data.0.sku', 'COLA-1');
        });

        it('narrows the report by store, date range, and search', function (array $query) {
            $this->getJson('/api/v1/reports/sales?'.http_build_query($query))
                ->assertOk()
                ->assertJsonCount(1, 'data')
                ->assertJsonPath('data.0.sku', 'COLA-1')
                ->assertJsonPath('data.0.sold_quantity', '1.0000')
                ->assertJsonPath('data.0.sold_amount', '10.0000')
                ->assertJsonPath('data.0.paid_amount', '4.0000');
        })->with([
            'store' => fn () => ['store_id' => $this->branchStore->id],
            'date range' => fn () => ['date_from' => '2026-10-02', 'date_to' => '2026-10-05'],
            'search with store' => fn () => ['search' => 'cola', 'store_id' => $this->branchStore->id],
        ]);

        it('sorts the rows by the requested column', function () {
            $this->getJson('/api/v1/reports/sales?sort_col=sku&sort_by=asc')
                ->assertOk()
                ->assertJsonPath('data.0.sku', 'CHIPS-1')
                ->assertJsonPath('data.1.sku', 'COLA-1');
        });

        it('pages the rows using the limit sent by the data table', function () {
            $this->getJson('/api/v1/reports/sales?limit=1')
                ->assertOk()
                ->assertJsonCount(1, 'data')
                ->assertJsonPath('meta.per_page', 1)
                ->assertJsonPath('meta.total', 2);
        });

        it('rejects an end date before the start date and an unknown sort column', function () {
            $this->getJson('/api/v1/reports/sales?date_from=2026-10-05&date_to=2026-10-01&sort_col=cost&sort_by=up')
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['date_to', 'sort_col', 'sort_by']);
        });
    });

    describe('summary', function () {
        it('totals the filtered report', function () {
            $this->getJson('/api/v1/reports/sales/summary')
                ->assertOk()
                ->assertExactJson(['data' => [
                    'total_products' => 2,
                    'sold_quantity' => '4.0000',
                    'sold_amount' => '37.0000',
                    'paid_amount' => '31.0000',
                    'due_amount' => '6.0000',
                ]]);
        });
    });

    describe('exports', function () {
        it('downloads the report as a pdf', function () {
            SnappyPdf::fake();

            $this->get('/api/v1/reports/sales/export/pdf?store_id='.$this->mainStore->id)
                ->assertOk()
                ->assertHeader('content-type', 'application/pdf');

            SnappyPdf::assertViewIs('inventory::reports.sales');
            SnappyPdf::assertSeeText('COLA-1');
            SnappyPdf::assertSeeText($this->mainStore->name);
            SnappyPdf::assertDontSeeText('FOREIGN-1');
        });

        it('downloads the report as an excel workbook', function () {
            Excel::fake();
            $this->travelTo('2026-10-12 10:30:00');

            $this->get('/api/v1/reports/sales/export/excel?brand_id='.$this->brandAcme->id)->assertOk();

            Excel::assertDownloaded('sales-report-2026-10-12-103000.xlsx', function (SalesReportExcelExport $export): bool {
                $sheet = $export->array();
                $colaRow = collect($sheet)->first(fn (array $row): bool => ($row[1] ?? null) === 'COLA-1');

                return $colaRow !== null
                    && $colaRow[6] === 3.0
                    && $colaRow[7] === 28.0
                    && $colaRow[8] === 22.0
                    && end($sheet)[0] === 'Total'
                    && in_array(['Brand', 'Acme'], $sheet, true);
            });
        });
    });
});
