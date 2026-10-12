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
use DA\Inventory\Supports\Reports\TopSalesReportExcelExport;
use Illuminate\Database\Eloquent\Model;
use Maatwebsite\Excel\Facades\Excel;

function topSalesVariant(int $tenantId, Brand $brand, Category $category, string $sku, string $name): ProductVariant
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
function topSalesSale(int $tenantId, array $lines, array $attributes): Sale
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
        '/api/v1/reports/top-sales',
        '/api/v1/reports/top-sales/summary',
        '/api/v1/reports/top-sales/export/pdf',
        '/api/v1/reports/top-sales/export/excel',
    ]);

    it('returns 403 when the user cannot view reports', function () {
        actingAsTenantUser(permissions: [Permission::SalesView]);

        $this->getJson('/api/v1/reports/top-sales')->assertForbidden();
    });
});

describe('with recorded sales', function () {
    beforeEach(function () {
        $this->user = actingAsTenantUser(permissions: [Permission::ReportsView]);
        $tenantId = (int) $this->user->tenant_id;

        $this->brandAcme = Brand::factory()->create(['tenant_id' => $tenantId, 'name' => 'Acme']);
        $this->brandOther = Brand::factory()->create(['tenant_id' => $tenantId, 'name' => 'Other']);
        $this->brandPure = Brand::factory()->create(['tenant_id' => $tenantId, 'name' => 'Pure']);
        $this->parentCategory = Category::factory()->create(['tenant_id' => $tenantId, 'name' => 'Drinks']);
        $this->childCategory = Category::factory()->childOf($this->parentCategory)->create(['name' => 'Soda']);
        $this->snackCategory = Category::factory()->create(['tenant_id' => $tenantId, 'name' => 'Snacks']);
        $this->waterCategory = Category::factory()->create(['tenant_id' => $tenantId, 'name' => 'Water']);
        $this->mainStore = Store::factory()->create(['tenant_id' => $tenantId]);
        $this->branchStore = Store::factory()->create(['tenant_id' => $tenantId]);

        $this->cola = topSalesVariant($tenantId, $this->brandAcme, $this->childCategory, 'COLA-1', 'Cola');
        $this->chips = topSalesVariant($tenantId, $this->brandOther, $this->snackCategory, 'CHIPS-1', 'Chips');
        $this->water = topSalesVariant($tenantId, $this->brandPure, $this->waterCategory, 'WATER-1', 'Water');

        // Lines total 30, invoice discount brings the grand total to 27: cola gets 18, chips gets 9.
        topSalesSale($tenantId, [[$this->cola, '2', '20'], [$this->chips, '1', '10']], [
            'store_id' => $this->mainStore->id,
            'sale_date' => '2026-10-01',
            'discount_amount' => '3',
            'grand_total' => '27',
            'paid_amount' => '27',
            'due_amount' => '0',
        ]);

        topSalesSale($tenantId, [[$this->cola, '1', '10']], [
            'store_id' => $this->branchStore->id,
            'sale_date' => '2026-10-05',
            'grand_total' => '10',
            'paid_amount' => '4',
            'due_amount' => '6',
        ]);

        topSalesSale($tenantId, [[$this->water, '10', '5']], [
            'store_id' => $this->mainStore->id,
            'sale_date' => '2026-10-01',
            'grand_total' => '5',
            'paid_amount' => '5',
            'due_amount' => '0',
        ]);

        Model::withoutEvents(function (): void {
            $otherTenant = Tenant::factory()->create();
            $otherVariant = topSalesVariant(
                $otherTenant->id,
                Brand::factory()->create(['tenant_id' => $otherTenant->id]),
                Category::factory()->create(['tenant_id' => $otherTenant->id]),
                'FOREIGN-1',
                'Foreign product',
            );

            topSalesSale($otherTenant->id, [[$otherVariant, '50', '500']], [
                'sale_date' => '2026-10-01',
                'grand_total' => '500',
                'paid_amount' => '500',
            ]);
        });
    });

    describe('report rows', function () {
        it('ranks products by sold quantity for the current tenant only', function () {
            $this->getJson('/api/v1/reports/top-sales')
                ->assertOk()
                ->assertJsonCount(3, 'data')
                ->assertJsonPath('meta.total', 3)
                ->assertJsonPath('data.0', [
                    'rank' => 1,
                    'product_variant_id' => $this->water->id,
                    'sku' => 'WATER-1',
                    'product_name' => 'Water',
                    'variant_name' => null,
                    'brand_name' => 'Pure',
                    'sold_quantity' => '10.0000',
                    'sold_amount' => '5.0000',
                ])
                ->assertJsonPath('data.1', [
                    'rank' => 2,
                    'product_variant_id' => $this->cola->id,
                    'sku' => 'COLA-1',
                    'product_name' => 'Cola',
                    'variant_name' => null,
                    'brand_name' => 'Acme',
                    'sold_quantity' => '3.0000',
                    'sold_amount' => '28.0000',
                ])
                ->assertJsonPath('data.2.sku', 'CHIPS-1')
                ->assertJsonPath('data.2.rank', 3)
                ->assertJsonPath('data.2.sold_quantity', '1.0000')
                ->assertJsonPath('data.2.sold_amount', '9.0000');
        });

        it('keeps the invoice share of each line when filtering by brand', function () {
            $this->getJson('/api/v1/reports/top-sales?brand_id='.$this->brandAcme->id)
                ->assertOk()
                ->assertJsonCount(1, 'data')
                ->assertJsonPath('meta.total', 1)
                ->assertJsonPath('data.0.sku', 'COLA-1')
                ->assertJsonPath('data.0.sold_amount', '28.0000');
        });

        it('narrows the report by store, date range, and search', function (array $query) {
            $this->getJson('/api/v1/reports/top-sales?'.http_build_query($query))
                ->assertOk()
                ->assertJsonCount(1, 'data')
                ->assertJsonPath('data.0.sku', 'COLA-1')
                ->assertJsonPath('data.0.sold_quantity', '1.0000')
                ->assertJsonPath('data.0.sold_amount', '10.0000');
        })->with([
            'store' => fn () => ['store_id' => $this->branchStore->id],
            'date range' => fn () => ['date_from' => '2026-10-02', 'date_to' => '2026-10-05'],
            'search with store' => fn () => ['search' => 'cola', 'store_id' => $this->branchStore->id],
        ]);

        it('does not treat search wildcards as a match-all', function () {
            $this->getJson('/api/v1/reports/top-sales?'.http_build_query(['search' => '%']))
                ->assertOk()
                ->assertJsonCount(0, 'data')
                ->assertJsonPath('meta.total', 0);
        });

        it('sorts the rows by the requested column', function (string $query, array $skus) {
            $this->getJson('/api/v1/reports/top-sales?'.$query)
                ->assertOk()
                ->assertJsonPath('data.0.sku', $skus[0])
                ->assertJsonPath('data.1.sku', $skus[1])
                ->assertJsonPath('data.2.sku', $skus[2]);
        })->with([
            'sku ascending' => ['sort_col=sku&sort_by=asc', ['CHIPS-1', 'COLA-1', 'WATER-1']],
            'sold amount descending' => ['sort_col=sold_amount&sort_by=desc', ['COLA-1', 'CHIPS-1', 'WATER-1']],
        ]);

        it('ranks a higher sold amount ahead of an equal sold quantity', function () {
            $tenantId = (int) $this->user->tenant_id;
            $premium = topSalesVariant($tenantId, $this->brandOther, $this->snackCategory, 'PREM-1', 'Premium');
            $budget = topSalesVariant($tenantId, $this->brandOther, $this->snackCategory, 'BUDG-1', 'Budget');

            topSalesSale($tenantId, [[$premium, '4', '40']], [
                'store_id' => $this->mainStore->id,
                'sale_date' => '2026-10-01',
                'grand_total' => '40',
                'paid_amount' => '40',
                'due_amount' => '0',
            ]);
            topSalesSale($tenantId, [[$budget, '4', '4']], [
                'store_id' => $this->mainStore->id,
                'sale_date' => '2026-10-01',
                'grand_total' => '4',
                'paid_amount' => '4',
                'due_amount' => '0',
            ]);

            $this->getJson('/api/v1/reports/top-sales')
                ->assertOk()
                ->assertJsonPath('data.0.sku', 'WATER-1')
                ->assertJsonPath('data.1.sku', 'PREM-1')
                ->assertJsonPath('data.2.sku', 'BUDG-1')
                ->assertJsonPath('data.3.sku', 'COLA-1');
        });

        it('continues the rank on the next page', function () {
            $this->getJson('/api/v1/reports/top-sales?per_page=1&page=2')
                ->assertOk()
                ->assertJsonPath('data.0.rank', 2)
                ->assertJsonPath('data.0.sku', 'COLA-1');
        });

        it('pages the rows using the limit sent by the data table', function () {
            $this->getJson('/api/v1/reports/top-sales?limit=1')
                ->assertOk()
                ->assertJsonCount(1, 'data')
                ->assertJsonPath('meta.per_page', 1)
                ->assertJsonPath('meta.total', 3)
                ->assertJsonPath('data.0.sku', 'WATER-1');
        });

        it('rejects an end date before the start date and an unknown sort column', function () {
            $this->getJson('/api/v1/reports/top-sales?date_from=2026-10-05&date_to=2026-10-01&sort_col=category_name&sort_by=up')
                ->assertUnprocessable()
                ->assertJsonValidationErrors([
                    'date_to' => 'The date to field must be a date after or equal to date from.',
                    'sort_col' => 'The selected sort col is invalid.',
                    'sort_by' => 'The selected sort by is invalid.',
                ]);
        });
    });

    describe('summary', function () {
        it('totals the filtered report', function () {
            $this->getJson('/api/v1/reports/top-sales/summary')
                ->assertOk()
                ->assertExactJson(['data' => [
                    'total_products' => 3,
                    'sold_quantity' => '14.0000',
                    'sold_amount' => '42.0000',
                ]]);
        });

        it('totals only the rows that match the brand filter', function () {
            $this->getJson('/api/v1/reports/top-sales/summary?brand_id='.$this->brandAcme->id)
                ->assertOk()
                ->assertJsonPath('data.total_products', 1)
                ->assertJsonPath('data.sold_quantity', '3.0000')
                ->assertJsonPath('data.sold_amount', '28.0000');
        });
    });

    describe('exports', function () {
        it('downloads the report as a pdf', function () {
            SnappyPdf::fake();

            $this->get('/api/v1/reports/top-sales/export/pdf?store_id='.$this->mainStore->id)
                ->assertOk()
                ->assertHeader('content-type', 'application/pdf');

            SnappyPdf::assertViewIs('inventory::reports.top-sales');
            SnappyPdf::assertSeeText('Products sold');
            SnappyPdf::assertSeeText('Units across the matching products');
            SnappyPdf::assertSeeText('WATER-1');
            SnappyPdf::assertSeeText($this->mainStore->name);
            SnappyPdf::assertDontSeeText('FOREIGN-1');
        });

        it('escapes product names in the pdf', function () {
            $tenantId = (int) $this->user->tenant_id;
            $unsafe = topSalesVariant(
                $tenantId,
                $this->brandAcme,
                $this->snackCategory,
                'XSS-1',
                'Bad <script>alert(1)</script>',
            );

            topSalesSale($tenantId, [[$unsafe, '1', '8']], [
                'store_id' => $this->mainStore->id,
                'sale_date' => '2026-10-01',
                'grand_total' => '8',
                'paid_amount' => '8',
                'due_amount' => '0',
            ]);

            SnappyPdf::fake();

            $this->get('/api/v1/reports/top-sales/export/pdf')->assertOk();

            SnappyPdf::assertDontSee('<script>');
            SnappyPdf::assertSee('&lt;script&gt;');
            SnappyPdf::assertSeeText('XSS-1');
        });

        it('downloads the report as an excel workbook', function () {
            Excel::fake();
            $this->travelTo('2026-10-12 10:30:00');

            $this->get('/api/v1/reports/top-sales/export/excel?brand_id='.$this->brandAcme->id)->assertOk();

            Excel::assertDownloaded('top-sales-report-2026-10-12-103000.xlsx', function (TopSalesReportExcelExport $export): bool {
                $sheet = $export->array();
                $colaRow = collect($sheet)->first(fn (array $row): bool => ($row[1] ?? null) === 'COLA-1');
                $totals = $sheet[array_key_last($sheet)];

                return $colaRow !== null
                    && $colaRow[5] === 3.0
                    && $colaRow[6] === 28.0
                    && $totals[0] === 'Total'
                    && $totals[5] === 3.0
                    && $totals[6] === 28.0
                    && in_array(['Brand', 'Acme'], $sheet, true)
                    && in_array(['Products sold', 1], $sheet, true)
                    && in_array(['Quantity sold', 3.0], $sheet, true)
                    && in_array(['Sold amount', 28.0], $sheet, true)
                    && collect($sheet)->every(fn (array $row): bool => ! in_array('Category', $row, true));
            });
        });
    });
});
