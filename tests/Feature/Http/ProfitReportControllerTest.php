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
use DA\Inventory\Supports\Reports\ProfitReportExcelExport;
use Illuminate\Database\Eloquent\Model;
use Maatwebsite\Excel\Facades\Excel;

function profitReportVariant(int $tenantId, Brand $brand, Category $category, string $sku, string $name): ProductVariant
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
 * @param  list<array{0: ProductVariant, 1: string, 2: string, 3: string, 4: string}>  $lines  variant, quantity, line total, tax, cogs
 * @param  array<string, mixed>  $attributes
 */
function profitReportSale(int $tenantId, array $lines, array $attributes): Sale
{
    $sale = Sale::factory()->create([...$attributes, 'tenant_id' => $tenantId]);

    foreach ($lines as [$variant, $quantity, $total, $tax, $cogs]) {
        SaleItem::factory()->create([
            'tenant_id' => $tenantId,
            'sale_id' => $sale->id,
            'product_variant_id' => $variant->id,
            'product_id' => $variant->product_id,
            'quantity' => $quantity,
            'tax_amount' => $tax,
            'subtotal' => bcsub($total, $tax, 4),
            'total' => $total,
            'cogs' => $cogs,
        ]);
    }

    return $sale;
}

describe('authentication and authorization', function () {
    it('returns 401 when no token is provided', function (string $uri) {
        $this->getJson($uri)->assertUnauthorized();
    })->with([
        '/api/v1/reports/profit',
        '/api/v1/reports/profit/summary',
        '/api/v1/reports/profit/export/pdf',
        '/api/v1/reports/profit/export/excel',
    ]);

    it('returns 403 when the user cannot view reports', function () {
        actingAsTenantUser(permissions: [Permission::SalesView]);

        $this->getJson('/api/v1/reports/profit')->assertForbidden();
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

        $this->cola = profitReportVariant($tenantId, $this->brandAcme, $this->childCategory, 'COLA-1', 'Cola');
        $this->chips = profitReportVariant($tenantId, $this->brandOther, $this->snackCategory, 'CHIPS-1', 'Chips');

        // Lines total 150, including 30 of exclusive tax. A 30 discount makes the grand total 120.
        // Cola net sales = (100 - 20) * 120 / 150 = 64. Chips net sales = (50 - 10) * 120 / 150 = 32.
        profitReportSale($tenantId, [
            [$this->cola, '2', '100', '20', '40'],
            [$this->chips, '1', '50', '10', '50'],
        ], [
            'store_id' => $this->mainStore->id,
            'sale_date' => '2026-10-01',
            'discount_amount' => '30',
            'tax_amount' => '30',
            'grand_total' => '120',
        ]);

        profitReportSale($tenantId, [[$this->cola, '1', '10', '0', '4']], [
            'store_id' => $this->branchStore->id,
            'sale_date' => '2026-10-05',
            'grand_total' => '10',
            'paid_amount' => '4',
            'due_amount' => '6',
        ]);

        Model::withoutEvents(function (): void {
            $otherTenant = Tenant::factory()->create();
            $otherVariant = profitReportVariant(
                $otherTenant->id,
                Brand::factory()->create(['tenant_id' => $otherTenant->id]),
                Category::factory()->create(['tenant_id' => $otherTenant->id]),
                'FOREIGN-1',
                'Foreign product',
            );

            profitReportSale($otherTenant->id, [[$otherVariant, '50', '500', '0', '100']], [
                'sale_date' => '2026-10-01',
                'grand_total' => '500',
            ]);
        });
    });

    describe('report rows', function () {
        it('calculates net sales after discount and exclusive tax, then gross profit from the sale cost', function () {
            $this->getJson('/api/v1/reports/profit')
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
                    'net_sales' => '74.0000',
                    'cogs' => '44.0000',
                    'gross_profit' => '30.0000',
                    'gross_margin' => '40.5405',
                ])
                ->assertJsonPath('data.1.sku', 'CHIPS-1')
                ->assertJsonPath('data.1.net_sales', '32.0000')
                ->assertJsonPath('data.1.cogs', '50.0000')
                ->assertJsonPath('data.1.gross_profit', '-18.0000')
                ->assertJsonPath('data.1.gross_margin', '-56.2500');
        });

        it('keeps the invoice share of each line when filtering by brand', function () {
            $this->getJson('/api/v1/reports/profit?brand_id='.$this->brandAcme->id)
                ->assertOk()
                ->assertJsonCount(1, 'data')
                ->assertJsonPath('data.0.sku', 'COLA-1')
                ->assertJsonPath('data.0.net_sales', '74.0000')
                ->assertJsonPath('data.0.gross_profit', '30.0000');
        });

        it('includes products from child categories when filtering by a parent category', function () {
            $this->getJson('/api/v1/reports/profit?category_id='.$this->parentCategory->id)
                ->assertOk()
                ->assertJsonCount(1, 'data')
                ->assertJsonPath('meta.total', 1)
                ->assertJsonPath('data.0.sku', 'COLA-1')
                ->assertJsonPath('data.0.net_sales', '74.0000');
        });

        it('narrows the report by store, date range, and search', function (array $query) {
            $this->getJson('/api/v1/reports/profit?'.http_build_query($query))
                ->assertOk()
                ->assertJsonCount(1, 'data')
                ->assertJsonPath('data.0.sku', 'COLA-1')
                ->assertJsonPath('data.0.sold_quantity', '1.0000')
                ->assertJsonPath('data.0.net_sales', '10.0000')
                ->assertJsonPath('data.0.gross_profit', '6.0000')
                ->assertJsonPath('data.0.gross_margin', '60.0000');
        })->with([
            'store' => fn () => ['store_id' => $this->branchStore->id],
            'date range' => fn () => ['date_from' => '2026-10-02', 'date_to' => '2026-10-05'],
            'search with store' => fn () => ['search' => 'cola', 'store_id' => $this->branchStore->id],
        ]);

        it('sorts the rows by the requested column', function () {
            $this->getJson('/api/v1/reports/profit?sort_col=gross_margin&sort_by=asc')
                ->assertOk()
                ->assertJsonPath('data.0.sku', 'CHIPS-1')
                ->assertJsonPath('data.1.sku', 'COLA-1');
        });

        it('pages the rows using the limit sent by the data table', function () {
            $this->getJson('/api/v1/reports/profit?limit=1')
                ->assertOk()
                ->assertJsonCount(1, 'data')
                ->assertJsonPath('meta.per_page', 1)
                ->assertJsonPath('meta.total', 2);
        });

        it('rejects an end date before the start date and an unknown sort column', function () {
            $this->getJson('/api/v1/reports/profit?date_from=2026-10-05&date_to=2026-10-01&sort_col=cost&sort_by=up')
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['date_to', 'sort_col', 'sort_by']);
        });
    });

    describe('summary', function () {
        it('totals net sales, cost, gross profit, margin, and products sold at a loss', function () {
            $this->getJson('/api/v1/reports/profit/summary')
                ->assertOk()
                ->assertExactJson(['data' => [
                    'total_products' => 2,
                    'loss_products' => 1,
                    'sold_quantity' => '4.0000',
                    'net_sales' => '106.0000',
                    'cogs' => '94.0000',
                    'gross_profit' => '12.0000',
                    'gross_margin' => '11.3208',
                ]]);
        });

        it('drops the loss count when the filtered products are profitable', function () {
            $this->getJson('/api/v1/reports/profit/summary?brand_id='.$this->brandAcme->id)
                ->assertOk()
                ->assertJsonPath('data.loss_products', 0)
                ->assertJsonPath('data.gross_profit', '30.0000')
                ->assertJsonPath('data.gross_margin', '40.5405');
        });
    });

    describe('exports', function () {
        it('downloads the report as a pdf', function () {
            SnappyPdf::fake();

            $this->get('/api/v1/reports/profit/export/pdf?store_id='.$this->mainStore->id)
                ->assertOk()
                ->assertHeader('content-type', 'application/pdf');

            SnappyPdf::assertViewIs('inventory::reports.profit');
            SnappyPdf::assertSeeText('Gross margin');
            SnappyPdf::assertSeeText('1 product sold at a loss');
            SnappyPdf::assertSeeText('COLA-1');
            SnappyPdf::assertSeeText($this->mainStore->name);
            SnappyPdf::assertDontSeeText('FOREIGN-1');
        });

        it('downloads the report as an excel workbook', function () {
            Excel::fake();
            $this->travelTo('2026-10-12 10:30:00');

            $this->get('/api/v1/reports/profit/export/excel?brand_id='.$this->brandAcme->id)->assertOk();

            Excel::assertDownloaded('profit-report-2026-10-12-103000.xlsx', function (ProfitReportExcelExport $export): bool {
                $sheet = $export->array();
                $colaRow = collect($sheet)->first(fn (array $row): bool => ($row[1] ?? null) === 'COLA-1');

                return $colaRow !== null
                    && $colaRow[6] === 3.0
                    && $colaRow[7] === 74.0
                    && $colaRow[8] === 44.0
                    && $colaRow[9] === 30.0
                    && $colaRow[10] === 40.5405
                    && end($sheet)[0] === 'Total'
                    && in_array(['Brand', 'Acme'], $sheet, true)
                    && in_array(['Net sales', 74.0], $sheet, true)
                    && in_array(['Cost of goods', 44.0], $sheet, true)
                    && in_array(['Gross profit', 30.0], $sheet, true)
                    && in_array(['Products sold at a loss', 0], $sheet, true)
                    && in_array(['Gross margin %', 40.5405], $sheet, true);
            });
        });
    });
});

describe('a fully discounted sale', function () {
    it('reports a loss with zero margin when the invoice total is zero', function () {
        $user = actingAsTenantUser(permissions: [Permission::ReportsView]);
        $tenantId = (int) $user->tenant_id;
        $variant = profitReportVariant(
            $tenantId,
            Brand::factory()->create(['tenant_id' => $tenantId]),
            Category::factory()->create(['tenant_id' => $tenantId]),
            'FREE-1',
            'Free sample',
        );

        profitReportSale($tenantId, [[$variant, '1', '10', '0', '4']], [
            'sale_date' => '2026-10-03',
            'discount_amount' => '10',
            'grand_total' => '0',
        ]);

        $this->getJson('/api/v1/reports/profit/summary')
            ->assertOk()
            ->assertJsonPath('data.loss_products', 1)
            ->assertJsonPath('data.net_sales', '0.0000')
            ->assertJsonPath('data.gross_profit', '-4.0000')
            ->assertJsonPath('data.gross_margin', '0.0000');
    });
});
