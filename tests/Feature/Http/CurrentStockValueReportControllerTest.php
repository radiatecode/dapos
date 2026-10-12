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
use DA\Inventory\Supports\Reports\CurrentStockValueReportExcelExport;
use Illuminate\Database\Eloquent\Model;
use Maatwebsite\Excel\Facades\Excel;

/**
 * @param  array<string, mixed>  $productAttributes
 * @param  array<string, mixed>  $variantAttributes
 */
function currentStockValueVariant(
    int $tenantId,
    Brand $brand,
    Category $category,
    string $sku,
    string $name,
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
        ...$variantAttributes,
    ]);
}

function currentStockValueBalance(
    ProductVariant $variant,
    Store $store,
    string $quantity,
    string $reserved,
    string $stockValue,
): InventoryBalance {
    return InventoryBalance::factory()->create([
        'tenant_id' => $variant->tenant_id,
        'store_id' => $store->id,
        'product_variant_id' => $variant->id,
        'quantity' => $quantity,
        'reserved_quantity' => $reserved,
        'available_quantity' => bcsub($quantity, $reserved, 4),
        'stock_value' => $stockValue,
    ]);
}

describe('authentication and authorization', function () {
    it('returns 401 when no token is provided', function (string $uri) {
        $this->getJson($uri)->assertUnauthorized();
    })->with([
        '/api/v1/reports/current-stock-value',
        '/api/v1/reports/current-stock-value/summary',
        '/api/v1/reports/current-stock-value/export/pdf',
        '/api/v1/reports/current-stock-value/export/excel',
    ]);

    it('returns 403 when the user cannot view reports', function () {
        actingAsTenantUser(permissions: [Permission::SalesView]);

        $this->getJson('/api/v1/reports/current-stock-value')->assertForbidden();
    });
});

describe('with stocked products', function () {
    beforeEach(function () {
        $this->user = actingAsTenantUser(permissions: [Permission::ReportsView]);
        $tenantId = (int) $this->user->tenant_id;

        $this->brandAcme = Brand::factory()->create(['tenant_id' => $tenantId, 'name' => 'Acme']);
        $this->brandOther = Brand::factory()->create(['tenant_id' => $tenantId, 'name' => 'Other']);
        $this->brandShelf = Brand::factory()->create(['tenant_id' => $tenantId, 'name' => 'Shelf']);
        $this->parentCategory = Category::factory()->create(['tenant_id' => $tenantId, 'name' => 'Drinks']);
        $this->childCategory = Category::factory()->childOf($this->parentCategory)->create(['name' => 'Soda']);
        $this->snackCategory = Category::factory()->create(['tenant_id' => $tenantId, 'name' => 'Snacks']);
        $this->householdCategory = Category::factory()->create(['tenant_id' => $tenantId, 'name' => 'Household']);
        $this->mainStore = Store::factory()->create(['tenant_id' => $tenantId]);
        $this->branchStore = Store::factory()->create(['tenant_id' => $tenantId]);

        // Cola: main 6 on hand (2 reserved, value 60) + branch 4 on hand (value 80) = 10 on hand, 8 available, value 140.
        // The catalog cost is deliberately different from the layer value.
        $this->cola = currentStockValueVariant(
            $tenantId,
            $this->brandAcme,
            $this->childCategory,
            'COLA-1',
            'Cola',
            variantAttributes: [
                'name' => '330ml',
                'barcode' => '5901234123457',
                'cost_price' => '1.0000',
            ],
        );
        currentStockValueBalance($this->cola, $this->mainStore, '6', '2', '60');
        currentStockValueBalance($this->cola, $this->branchStore, '4', '0', '80');

        // Juice is fully reserved, so it has value but nothing left to sell.
        $this->juice = currentStockValueVariant($tenantId, $this->brandOther, $this->snackCategory, 'JUICE-1', 'Juice');
        currentStockValueBalance($this->juice, $this->branchStore, '5', '5', '25');

        // Inactive products stay in the report while they still have stock.
        $this->idle = currentStockValueVariant(
            $tenantId,
            $this->brandShelf,
            $this->householdCategory,
            'IDLE-1',
            'Idle',
            ['is_active' => false],
        );
        currentStockValueBalance($this->idle, $this->mainStore, '2', '0', '10');

        $water = currentStockValueVariant($tenantId, $this->brandAcme, $this->childCategory, 'WATER-1', 'Water');
        currentStockValueBalance($water, $this->mainStore, '0', '0', '0');

        currentStockValueVariant($tenantId, $this->brandOther, $this->snackCategory, 'CHIPS-1', 'Chips');

        $deleted = currentStockValueVariant($tenantId, $this->brandAcme, $this->childCategory, 'GONE-1', 'Deleted');
        currentStockValueBalance($deleted, $this->mainStore, '3', '0', '30');
        $deleted->delete();

        Model::withoutEvents(function (): void {
            $otherTenant = Tenant::factory()->create();
            $foreign = currentStockValueVariant(
                $otherTenant->id,
                Brand::factory()->create(['tenant_id' => $otherTenant->id]),
                Category::factory()->create(['tenant_id' => $otherTenant->id]),
                'FOREIGN-1',
                'Foreign product',
            );
            $store = Store::factory()->create(['tenant_id' => $otherTenant->id]);
            currentStockValueBalance($foreign, $store, '9', '0', '90');
        });
    });

    describe('report rows', function () {
        it('lists on-hand stock with available quantity and the stored stock value', function () {
            $this->getJson('/api/v1/reports/current-stock-value')
                ->assertOk()
                ->assertJsonCount(3, 'data')
                ->assertJsonPath('meta.total', 3)
                ->assertJsonPath('data.0', [
                    'product_variant_id' => $this->cola->id,
                    'sku' => 'COLA-1',
                    'product_name' => 'Cola',
                    'variant_name' => '330ml',
                    'brand_name' => 'Acme',
                    'category_name' => 'Soda',
                    'unit_code' => $this->cola->product->unit->code,
                    'on_hand_quantity' => '10.0000',
                    'available_quantity' => '8.0000',
                    'unit_cost' => '14.0000',
                    'stock_value' => '140.0000',
                ])
                ->assertJsonPath('data.1.sku', 'JUICE-1')
                ->assertJsonPath('data.1.available_quantity', '0.0000')
                ->assertJsonPath('data.1.stock_value', '25.0000')
                ->assertJsonPath('data.2.sku', 'IDLE-1');
        });

        it('uses only the selected store when filtering by store', function () {
            $this->getJson('/api/v1/reports/current-stock-value?store_id='.$this->mainStore->id)
                ->assertOk()
                ->assertJsonCount(2, 'data')
                ->assertJsonPath('data.0.sku', 'COLA-1')
                ->assertJsonPath('data.0.on_hand_quantity', '6.0000')
                ->assertJsonPath('data.0.available_quantity', '4.0000')
                ->assertJsonPath('data.0.unit_cost', '10.0000')
                ->assertJsonPath('data.0.stock_value', '60.0000')
                ->assertJsonPath('data.1.sku', 'IDLE-1');
        });

        it('narrows the report by brand, parent category, and search', function (array $query, string $sku) {
            $this->getJson('/api/v1/reports/current-stock-value?'.http_build_query($query))
                ->assertOk()
                ->assertJsonCount(1, 'data')
                ->assertJsonPath('data.0.sku', $sku);
        })->with([
            'brand' => fn () => [['brand_id' => $this->brandAcme->id], 'COLA-1'],
            'parent category' => fn () => [['category_id' => $this->parentCategory->id], 'COLA-1'],
            'product name' => fn () => [['search' => 'cola'], 'COLA-1'],
            'barcode' => fn () => [['search' => '5901234123457'], 'COLA-1'],
            'variant name' => fn () => [['search' => '330ml'], 'COLA-1'],
            'reserved only' => fn () => [['availability' => 'reserved'], 'JUICE-1'],
        ]);

        it('keeps only variants that can still be sold when availability is available', function () {
            $this->getJson('/api/v1/reports/current-stock-value?availability=available')
                ->assertOk()
                ->assertJsonCount(2, 'data')
                ->assertJsonPath('data.0.sku', 'COLA-1')
                ->assertJsonPath('data.1.sku', 'IDLE-1');
        });

        it('does not treat a percent sign as a wildcard', function () {
            $this->getJson('/api/v1/reports/current-stock-value?search='.urlencode('%'))
                ->assertOk()
                ->assertJsonCount(0, 'data');
        });

        it('sorts the rows by the requested column', function () {
            $this->getJson('/api/v1/reports/current-stock-value?sort_col=available_quantity&sort_by=asc')
                ->assertOk()
                ->assertJsonPath('data.0.sku', 'JUICE-1')
                ->assertJsonPath('data.1.sku', 'IDLE-1')
                ->assertJsonPath('data.2.sku', 'COLA-1');
        });

        it('pages the rows using the limit sent by the data table', function () {
            $this->getJson('/api/v1/reports/current-stock-value?limit=1')
                ->assertOk()
                ->assertJsonCount(1, 'data')
                ->assertJsonPath('meta.per_page', 1)
                ->assertJsonPath('meta.total', 3);
        });

        it('rejects an unknown availability and sort column', function () {
            $this->getJson('/api/v1/reports/current-stock-value?availability=empty&sort_col=price&sort_by=up')
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['availability', 'sort_col', 'sort_by']);
        });
    });

    describe('summary', function () {
        it('counts products that can be sold and totals available quantity and stock value', function () {
            $this->getJson('/api/v1/reports/current-stock-value/summary')
                ->assertOk()
                ->assertExactJson(['data' => [
                    'available_products' => 2,
                    'total_available_quantity' => '10.0000',
                    'total_on_hand_quantity' => '17.0000',
                    'total_stock_value' => '175.0000',
                ]]);
        });

        it('totals only the selected store', function () {
            $this->getJson('/api/v1/reports/current-stock-value/summary?store_id='.$this->mainStore->id)
                ->assertOk()
                ->assertExactJson(['data' => [
                    'available_products' => 2,
                    'total_available_quantity' => '6.0000',
                    'total_on_hand_quantity' => '8.0000',
                    'total_stock_value' => '70.0000',
                ]]);
        });
    });

    describe('exports', function () {
        it('downloads the report as a pdf', function () {
            SnappyPdf::fake();

            $hostile = currentStockValueVariant(
                (int) $this->user->tenant_id,
                $this->brandShelf,
                $this->householdCategory,
                'HOSTILE-1',
                "<script>alert('x')</script>",
            );
            currentStockValueBalance($hostile, $this->mainStore, '1', '0', '4');

            $this->get('/api/v1/reports/current-stock-value/export/pdf?store_id='.$this->mainStore->id)
                ->assertOk()
                ->assertHeader('content-type', 'application/pdf');

            SnappyPdf::assertViewIs('inventory::reports.current-stock-value');
            SnappyPdf::assertSeeText('Available products');
            SnappyPdf::assertSeeText('Variants that can be sold now');
            SnappyPdf::assertSeeText('On-hand quantity × average cost');
            SnappyPdf::assertSeeText('COLA-1');
            SnappyPdf::assertSeeText('IDLE-1');
            SnappyPdf::assertSeeText($this->mainStore->name);
            SnappyPdf::assertDontSeeText('JUICE-1');
            SnappyPdf::assertDontSeeText('WATER-1');
            SnappyPdf::assertDontSeeText('FOREIGN-1');
            SnappyPdf::assertSee('&lt;script&gt;alert(&#039;x&#039;)&lt;/script&gt;');
            SnappyPdf::assertDontSee("<script>alert('x')</script>");
        });

        it('downloads the report as an excel workbook', function () {
            Excel::fake();
            $this->travelTo('2026-10-12 10:30:00');

            $this->get('/api/v1/reports/current-stock-value/export/excel?brand_id='.$this->brandAcme->id)->assertOk();

            Excel::assertDownloaded('current-stock-value-report-2026-10-12-103000.xlsx', function (CurrentStockValueReportExcelExport $export): bool {
                $sheet = $export->array();
                $colaRow = collect($sheet)->first(fn (array $row): bool => ($row[1] ?? null) === 'COLA-1');
                $stockValueRow = collect($sheet)->first(
                    fn (array $row): bool => is_string($row[0] ?? null) && str_starts_with($row[0], 'Stock value'),
                );

                return $colaRow !== null
                    && $colaRow[7] === 8.0
                    && $colaRow[8] === 10.0
                    && $colaRow[9] === 14.0
                    && $colaRow[10] === 140.0
                    && end($sheet)[0] === 'Total'
                    && end($sheet)[7] === 8.0
                    && end($sheet)[8] === 10.0
                    && end($sheet)[10] === 140.0
                    && in_array(['Brand', 'Acme'], $sheet, true)
                    && in_array(['Available products', 1], $sheet, true)
                    && in_array(['Available quantity', 8.0], $sheet, true)
                    && in_array(['On-hand quantity', 10.0], $sheet, true)
                    && $stockValueRow !== null
                    && $stockValueRow[1] === 140.0;
            });
        });
    });
});
