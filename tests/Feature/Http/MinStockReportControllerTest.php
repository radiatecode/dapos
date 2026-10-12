<?php

use App\Enums\Permission;
use Barryvdh\Snappy\Facades\SnappyPdf;
use DA\Admin\Models\Tenant;
use DA\Inventory\Models\Brand;
use DA\Inventory\Models\Category;
use DA\Inventory\Models\InventoryBalance;
use DA\Inventory\Models\Product;
use DA\Inventory\Models\ProductVariant;
use DA\Inventory\Models\Store;
use DA\Inventory\Supports\Reports\MinStockReportExcelExport;
use Illuminate\Database\Eloquent\Model;
use Maatwebsite\Excel\Facades\Excel;

/**
 * @param  array<string, mixed>  $productAttributes
 * @param  array<string, mixed>  $variantAttributes
 */
function minStockReportVariant(
    int $tenantId,
    Brand $brand,
    Category $category,
    string $sku,
    string $name,
    ?int $minStockLevel,
    array $productAttributes = [],
    array $variantAttributes = [],
): ProductVariant {
    $product = Product::factory()->create([
        'tenant_id' => $tenantId,
        'brand_id' => $brand->id,
        'category_id' => $category->id,
        'name' => $name,
        ...$productAttributes,
    ]);

    return ProductVariant::factory()->create([
        'tenant_id' => $tenantId,
        'product_id' => $product->id,
        'sku' => $sku,
        'min_stock_level' => $minStockLevel,
        ...$variantAttributes,
    ]);
}

function minStockReportBalance(ProductVariant $variant, Store $store, string $quantity, string $reserved = '0'): InventoryBalance
{
    return InventoryBalance::factory()->create([
        'tenant_id' => $variant->tenant_id,
        'store_id' => $store->id,
        'product_variant_id' => $variant->id,
        'quantity' => $quantity,
        'reserved_quantity' => $reserved,
        'available_quantity' => bcsub($quantity, $reserved, 4),
    ]);
}

describe('authentication and authorization', function () {
    it('returns 401 when no token is provided', function (string $uri) {
        $this->getJson($uri)->assertUnauthorized();
    })->with([
        '/api/v1/reports/min-stock',
        '/api/v1/reports/min-stock/summary',
        '/api/v1/reports/min-stock/export/pdf',
        '/api/v1/reports/min-stock/export/excel',
    ]);

    it('returns 403 when the user cannot view reports', function () {
        actingAsTenantUser(permissions: [Permission::SalesView]);

        $this->getJson('/api/v1/reports/min-stock')->assertForbidden();
    });
});

describe('with stocked products', function () {
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

        // Cola: 3 available in main (5 on hand, 2 reserved) + 4 in branch = 7 against a level of 10.
        $this->cola = minStockReportVariant($tenantId, $this->brandAcme, $this->childCategory, 'COLA-1', 'Cola', 10);
        minStockReportBalance($this->cola, $this->mainStore, '5', '2');
        minStockReportBalance($this->cola, $this->branchStore, '4');

        // Chips: never stocked.
        $this->chips = minStockReportVariant($tenantId, $this->brandOther, $this->snackCategory, 'CHIPS-1', 'Chips', 5);

        $water = minStockReportVariant($tenantId, $this->brandAcme, $this->childCategory, 'WATER-1', 'Water', 5);
        minStockReportBalance($water, $this->mainStore, '20');

        minStockReportVariant($tenantId, $this->brandAcme, $this->snackCategory, 'NO-LEVEL-1', 'No level', null);
        minStockReportVariant($tenantId, $this->brandAcme, $this->snackCategory, 'INACTIVE-1', 'Inactive', 5, ['is_active' => false]);
        minStockReportVariant($tenantId, $this->brandAcme, $this->snackCategory, 'SERVICE-1', 'Untracked', 5, ['track_inventory' => false]);
        minStockReportVariant($tenantId, $this->brandAcme, $this->snackCategory, 'OFF-1', 'Inactive variant', 5, variantAttributes: ['is_active' => false]);
        minStockReportVariant($tenantId, $this->brandAcme, $this->snackCategory, 'GONE-1', 'Deleted', 5)->delete();

        Model::withoutEvents(function (): void {
            $otherTenant = Tenant::factory()->create();

            minStockReportVariant(
                $otherTenant->id,
                Brand::factory()->create(['tenant_id' => $otherTenant->id]),
                Category::factory()->create(['tenant_id' => $otherTenant->id]),
                'FOREIGN-1',
                'Foreign product',
                100,
            );
        });
    });

    describe('report rows', function () {
        it('lists only active tracked variants whose available stock is at or below the min level', function () {
            $this->getJson('/api/v1/reports/min-stock')
                ->assertOk()
                ->assertJsonCount(2, 'data')
                ->assertJsonPath('meta.total', 2)
                ->assertJsonPath('data.0', [
                    'product_variant_id' => $this->chips->id,
                    'sku' => 'CHIPS-1',
                    'product_name' => 'Chips',
                    'variant_name' => $this->chips->name,
                    'brand_name' => 'Other',
                    'category_name' => 'Snacks',
                    'unit_code' => $this->chips->product->unit->code,
                    'current_stock' => '0.0000',
                    'min_stock_level' => 5,
                    'shortage_quantity' => '5.0000',
                    'stock_status' => 'out_of_stock',
                ])
                ->assertJsonPath('data.1.sku', 'COLA-1')
                ->assertJsonPath('data.1.current_stock', '7.0000')
                ->assertJsonPath('data.1.shortage_quantity', '3.0000')
                ->assertJsonPath('data.1.stock_status', 'low_stock');
        });

        it('compares only the selected store stock when filtering by store', function () {
            $this->getJson('/api/v1/reports/min-stock?store_id='.$this->branchStore->id.'&search=cola')
                ->assertOk()
                ->assertJsonCount(1, 'data')
                ->assertJsonPath('data.0.current_stock', '4.0000')
                ->assertJsonPath('data.0.shortage_quantity', '6.0000');
        });

        it('narrows the report by brand, parent category, search, and stock status', function (array $query, string $sku) {
            $this->getJson('/api/v1/reports/min-stock?'.http_build_query($query))
                ->assertOk()
                ->assertJsonCount(1, 'data')
                ->assertJsonPath('data.0.sku', $sku);
        })->with([
            'brand' => fn () => [['brand_id' => $this->brandAcme->id], 'COLA-1'],
            'parent category' => fn () => [['category_id' => $this->parentCategory->id], 'COLA-1'],
            'search' => fn () => [['search' => 'chip'], 'CHIPS-1'],
            'out of stock' => fn () => [['stock_status' => 'out_of_stock'], 'CHIPS-1'],
            'low stock' => fn () => [['stock_status' => 'low_stock'], 'COLA-1'],
        ]);

        it('sorts the rows by the requested column', function () {
            $this->getJson('/api/v1/reports/min-stock?sort_col=min_stock_level&sort_by=desc')
                ->assertOk()
                ->assertJsonPath('data.0.sku', 'COLA-1')
                ->assertJsonPath('data.1.sku', 'CHIPS-1');
        });

        it('pages the rows using the limit sent by the data table', function () {
            $this->getJson('/api/v1/reports/min-stock?limit=1')
                ->assertOk()
                ->assertJsonCount(1, 'data')
                ->assertJsonPath('meta.per_page', 1)
                ->assertJsonPath('meta.total', 2);
        });

        it('rejects an unknown stock status and sort column', function () {
            $this->getJson('/api/v1/reports/min-stock?stock_status=empty&sort_col=price&sort_by=up')
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['stock_status', 'sort_col', 'sort_by']);
        });
    });

    describe('summary', function () {
        it('counts out of stock and low stock items and totals the reorder quantity', function () {
            $this->getJson('/api/v1/reports/min-stock/summary')
                ->assertOk()
                ->assertExactJson(['data' => [
                    'total_items' => 2,
                    'out_of_stock_items' => 1,
                    'low_stock_items' => 1,
                    'shortage_quantity' => '8.0000',
                ]]);
        });
    });

    describe('exports', function () {
        it('downloads the report as a pdf', function () {
            SnappyPdf::fake();

            $this->get('/api/v1/reports/min-stock/export/pdf?store_id='.$this->mainStore->id)
                ->assertOk()
                ->assertHeader('content-type', 'application/pdf');

            SnappyPdf::assertViewIs('inventory::reports.min-stock');
            SnappyPdf::assertSeeText('Items to reorder');
            SnappyPdf::assertSeeText('Needed to bring 2 items back to the min level');
            SnappyPdf::assertSeeText('COLA-1');
            SnappyPdf::assertSeeText('CHIPS-1');
            SnappyPdf::assertSeeText($this->mainStore->name);
            SnappyPdf::assertDontSeeText('WATER-1');
            SnappyPdf::assertDontSeeText('FOREIGN-1');
        });

        it('downloads the report as an excel workbook', function () {
            Excel::fake();
            $this->travelTo('2026-10-12 10:30:00');

            $this->get('/api/v1/reports/min-stock/export/excel?brand_id='.$this->brandAcme->id)->assertOk();

            Excel::assertDownloaded('min-stock-report-2026-10-12-103000.xlsx', function (MinStockReportExcelExport $export): bool {
                $sheet = $export->array();
                $colaRow = collect($sheet)->first(fn (array $row): bool => ($row[1] ?? null) === 'COLA-1');

                return $colaRow !== null
                    && $colaRow[7] === 7.0
                    && $colaRow[8] === 10
                    && $colaRow[9] === 3.0
                    && $colaRow[10] === 'Low stock'
                    && end($sheet)[0] === 'Total'
                    && end($sheet)[9] === 3.0
                    && in_array(['Brand', 'Acme'], $sheet, true)
                    && in_array(['Items to reorder', 1], $sheet, true)
                    && in_array(['Out of stock', 0], $sheet, true)
                    && in_array(['Low stock', 1], $sheet, true)
                    && in_array(['Total reorder qty', 3.0], $sheet, true);
            });
        });
    });
});
