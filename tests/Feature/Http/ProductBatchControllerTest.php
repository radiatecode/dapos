<?php

use App\Enums\Permission;
use DA\Admin\Models\Tenant;
use DA\Inventory\Enums\UnitType;
use DA\Inventory\Models\Brand;
use DA\Inventory\Models\Category;
use DA\Inventory\Models\Product;
use DA\Inventory\Models\ProductVariant;
use DA\Inventory\Models\Store;
use DA\Inventory\Models\Unit;
use DA\Inventory\Supports\Product\ProductBatchColumns;
use DA\Inventory\Supports\Product\ProductBatchSampleExport;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * @return array{category: Category, brand: Brand, unit: Unit, store: Store}
 */
function batchProductCatalog(): array
{
    $unit = new Unit;
    $unit->name = 'Piece';
    $unit->code = 'PC'.fake()->unique()->numerify('###');
    $unit->unit_type = UnitType::Quantity;
    $unit->precision = 0;
    $unit->is_active = true;
    $unit->save();

    return [
        'category' => Category::factory()->create(['name' => 'Beverages']),
        'brand' => Brand::factory()->create(['name' => 'Acme']),
        'unit' => $unit,
        'store' => Store::factory()->create(['name' => 'Main Store']),
    ];
}

/**
 * @param  array<int, string|null>  $overrides
 * @return list<string>
 */
function batchProductRow(array $overrides = []): array
{
    $row = [
        'Cola 500ml',
        'Sparkling drink',
        'Beverages',
        'Acme',
        'Piece',
        'COLA-500',
        '1234567890123',
        '10',
        '15',
        '8',
        '5',
    ];

    foreach ($overrides as $index => $value) {
        $row[$index] = $value;
    }

    return $row;
}

/**
 * @param  list<list<string|null>>  $dataRows
 * @param  list<string>|null  $headings
 */
function productBatchSpreadsheet(array $dataRows, ?array $headings = null): UploadedFile
{
    $sheetRows = [
        ['Radiate Shop'],
        ['12 Market Road, Dhaka'],
        $headings ?? ProductBatchColumns::HEADINGS,
        ...$dataRows,
    ];

    $spreadsheet = new Spreadsheet;
    $sheet = $spreadsheet->getActiveSheet();

    foreach ($sheetRows as $rowIndex => $row) {
        foreach (array_values($row) as $columnIndex => $value) {
            $sheet->setCellValueExplicit(
                Coordinate::stringFromColumnIndex($columnIndex + 1).($rowIndex + 1),
                $value === null ? '' : (string) $value,
                DataType::TYPE_STRING,
            );
        }
    }

    $temporary = tempnam(sys_get_temp_dir(), 'product-batch');
    $path = $temporary.'.xlsx';
    rename($temporary, $path);
    (new Xlsx($spreadsheet))->save($path);
    $spreadsheet->disconnectWorksheets();

    return new UploadedFile(
        $path,
        'products.xlsx',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        null,
        true,
    );
}

beforeEach(function () {
    config()->set('excel.temporary_files.local_path', sys_get_temp_dir());
});

describe('authentication and authorization', function () {
    it('returns 401 when no token is provided', function (string $method, string $uri) {
        $this->json($method, $uri)->assertUnauthorized();
    })->with([
        ['GET', '/api/v1/products/batch/sample'],
        ['POST', '/api/v1/products/batch/verify'],
        ['POST', '/api/v1/products/batch'],
    ]);

    it('returns 403 when the user cannot create products', function () {
        actingAsTenantUser(permissions: [Permission::ProductsView]);

        $this->getJson('/api/v1/products/batch/sample')->assertForbidden();
        $this->postJson('/api/v1/products/batch/verify')->assertForbidden();
        $this->postJson('/api/v1/products/batch')->assertForbidden();
    });
});

describe('sample workbook', function () {
    it('downloads the tenant name, address, and highlighted headings', function () {
        $tenant = Tenant::factory()->create([
            'name' => 'Radiate Shop',
            'address_line_1' => '12 Market Road',
            'address_line_2' => null,
            'city' => 'Dhaka',
            'state' => null,
            'postal_code' => '1205',
            'country' => 'Bangladesh',
        ]);
        actingAsTenantUser($tenant, [Permission::ProductsCreate]);
        Excel::fake();

        $this->get('/api/v1/products/batch/sample')->assertOk();

        Excel::assertDownloaded('product-batch-sample.xlsx', function (ProductBatchSampleExport $export): bool {
            $worksheet = (new Spreadsheet)->getActiveSheet();
            $styles = $export->styles($worksheet);

            return $export->array() === [
                ['Radiate Shop'],
                ['12 Market Road, Dhaka, 1205, Bangladesh'],
                ProductBatchColumns::HEADINGS,
            ]
                && $export->title() === 'Products'
                && ($styles[1]['font']['bold'] ?? false) === true
                && ($styles[3]['font']['bold'] ?? false) === true
                && ($styles[3]['font']['color']['rgb'] ?? null) === 'FFFFFF'
                && ($styles[3]['fill']['startColor']['rgb'] ?? null) === '1B4F72';
        });
    });
});

describe('verification', function () {
    it('returns 422 when the file and store are missing', function () {
        actingAsTenantUser(permissions: [Permission::ProductsCreate]);

        $this->post('/api/v1/products/batch/verify', [], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'file' => 'The spreadsheet file is required.',
                'store_id' => 'The store field is required.',
            ]);
    });

    it('returns 422 when the headings do not match the sample', function () {
        actingAsTenantUser(permissions: [Permission::ProductsCreate]);
        $catalog = batchProductCatalog();
        $headings = ProductBatchColumns::HEADINGS;
        $headings[0] = 'Product';

        $this->post('/api/v1/products/batch/verify', [
            'file' => productBatchSpreadsheet([batchProductRow()], $headings),
            'store_id' => $catalog['store']->id,
        ], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'file' => 'The spreadsheet headings do not match the sample file.',
            ]);
    });

    it('returns 422 when the spreadsheet has no products', function () {
        actingAsTenantUser(permissions: [Permission::ProductsCreate]);
        $catalog = batchProductCatalog();

        $this->post('/api/v1/products/batch/verify', [
            'file' => productBatchSpreadsheet([]),
            'store_id' => $catalog['store']->id,
        ], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'file' => 'The spreadsheet does not contain any products.',
            ]);
    });

    it('returns 422 when the spreadsheet contains more than 500 products', function () {
        actingAsTenantUser(permissions: [Permission::ProductsCreate]);
        $catalog = batchProductCatalog();
        $rows = [];

        for ($index = 0; $index < 501; $index++) {
            $rows[] = batchProductRow([
                0 => 'Product '.$index,
                5 => 'SKU-'.$index,
                6 => 'BC-'.$index,
            ]);
        }

        $this->post('/api/v1/products/batch/verify', [
            'file' => productBatchSpreadsheet($rows),
            'store_id' => $catalog['store']->id,
        ], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'file' => 'The spreadsheet may not contain more than 500 products.',
            ]);

        expect(Product::query()->count())->toBe(0);
    });

    it('returns 422 when a product cell is invalid', function (int $column, string $value, string $message) {
        actingAsTenantUser(permissions: [Permission::ProductsCreate]);
        $catalog = batchProductCatalog();

        $this->post('/api/v1/products/batch/verify', [
            'file' => productBatchSpreadsheet([batchProductRow([$column => $value])]),
            'store_id' => $catalog['store']->id,
        ], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonPath('valid', false)
            ->assertJsonPath('rows.0.row', 4)
            ->assertJsonPath('rows.0.status', 'error')
            ->assertJsonPath('rows.0.errors.0', $message)
            ->assertJsonPath('summary.invalid', 1);

        expect(Product::query()->count())->toBe(0);
    })->with([
        'name' => [0, '', 'Name is required.'],
        'category' => [2, '', 'Category is required.'],
        'brand' => [3, '', 'Brand is required.'],
        'unit' => [4, '', 'Unit is required.'],
        'cost price' => [7, '', 'Cost price is required.'],
        'selling price' => [8, '', 'Selling price is required.'],
        'min stock' => [10, '', 'Min stock level is required.'],
        'cost price text' => [7, 'abc', 'Cost price must be a number greater than or equal to 0.'],
        'too many decimals' => [7, '1.23456', 'Cost price may not have more than 4 decimal places.'],
        'negative quantity' => [9, '-1', 'Quantity must be a number greater than or equal to 0.'],
        'fractional min stock' => [10, '1.5', 'Min stock level must be a whole number greater than or equal to 0.'],
        'long name' => [0, str_repeat('a', 256), 'Name may not be greater than 255 characters.'],
    ]);

    it('returns 422 when the category belongs to another tenant', function () {
        actingAsTenantUser(permissions: [Permission::ProductsCreate]);
        Category::factory()->create(['name' => 'Secret']);

        actingAsTenantUser(permissions: [Permission::ProductsCreate]);
        $catalog = batchProductCatalog();

        $this->post('/api/v1/products/batch/verify', [
            'file' => productBatchSpreadsheet([batchProductRow([2 => 'Secret'])]),
            'store_id' => $catalog['store']->id,
        ], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonPath('rows.0.errors.0', 'Category "Secret" was not found.');

        expect(Product::query()->count())->toBe(0);
    });

    it('returns 422 when the category name matches more than one record', function () {
        actingAsTenantUser(permissions: [Permission::ProductsCreate]);
        $catalog = batchProductCatalog();
        Category::factory()->create(['name' => 'Beverages']);

        $this->post('/api/v1/products/batch/verify', [
            'file' => productBatchSpreadsheet([batchProductRow()]),
            'store_id' => $catalog['store']->id,
        ], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonPath('rows.0.errors.0', 'Category "Beverages" matches more than one record.');
    });

    it('returns 422 when a store from another tenant is selected', function () {
        actingAsTenantUser(permissions: [Permission::ProductsCreate]);
        $foreignStore = Store::factory()->create();

        actingAsTenantUser(permissions: [Permission::ProductsCreate]);
        batchProductCatalog();

        $this->post('/api/v1/products/batch/verify', [
            'file' => productBatchSpreadsheet([batchProductRow()]),
            'store_id' => $foreignStore->id,
        ], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'store_id' => 'The selected store is invalid.',
            ]);

        expect(Product::query()->count())->toBe(0);
    });

    it('returns 422 when the store is inactive', function () {
        actingAsTenantUser(permissions: [Permission::ProductsCreate]);
        $catalog = batchProductCatalog();
        $catalog['store']->is_active = false;
        $catalog['store']->save();

        $this->post('/api/v1/products/batch/verify', [
            'file' => productBatchSpreadsheet([batchProductRow()]),
            'store_id' => $catalog['store']->id,
        ], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'store_id' => 'The selected store is invalid.',
            ]);
    });

    it('returns every row and marks only the invalid ones', function () {
        actingAsTenantUser(permissions: [Permission::ProductsCreate]);
        $catalog = batchProductCatalog();

        $response = $this->post('/api/v1/products/batch/verify', [
            'file' => productBatchSpreadsheet([
                batchProductRow(),
                array_fill(0, 11, ''),
                batchProductRow([0 => '', 5 => 'WATER-1', 6 => '999']),
            ]),
            'store_id' => $catalog['store']->id,
        ], ['Accept' => 'application/json']);

        $response->assertUnprocessable()
            ->assertJsonPath('message', 'The spreadsheet has invalid rows.')
            ->assertJsonPath('valid', false)
            ->assertJsonPath('summary.total', 2)
            ->assertJsonPath('summary.valid', 1)
            ->assertJsonPath('summary.invalid', 1)
            ->assertJsonPath('rows.0.row', 4)
            ->assertJsonPath('rows.0.status', 'ok')
            ->assertJsonPath('rows.0.errors', [])
            ->assertJsonPath('rows.1.row', 6)
            ->assertJsonPath('rows.1.status', 'error')
            ->assertJsonPath('rows.1.errors.0', 'Name is required.');

        expect(Product::query()->count())->toBe(0);
    });

    it('returns 422 when the sku or barcode is already used or repeated', function () {
        actingAsTenantUser(permissions: [Permission::ProductsCreate]);
        $catalog = batchProductCatalog();

        $this->postJson('/api/v1/products', productPayload([
            'category_id' => $catalog['category']->id,
            'brand_id' => $catalog['brand']->id,
            'unit_id' => $catalog['unit']->id,
            'store_id' => $catalog['store']->id,
            'name' => 'Existing Cola',
            'variants' => [[
                'sku' => 'TAKEN-SKU',
                'barcode' => 'TAKEN-BARCODE',
                'barcode_type' => 'CODE128',
                'cost_price' => '10',
                'selling_price' => '15',
                'quantity' => '1',
                'min_stock_level' => 1,
            ]],
        ]))->assertCreated();

        $this->post('/api/v1/products/batch/verify', [
            'file' => productBatchSpreadsheet([
                batchProductRow([5 => 'TAKEN-SKU', 6 => 'NEW-BARCODE']),
                batchProductRow([0 => 'Water', 5 => 'NEW-SKU', 6 => 'taken-barcode']),
                batchProductRow([0 => 'Juice', 5 => 'DUP-SKU', 6 => 'DUP-1']),
                batchProductRow([0 => 'Juice 2', 5 => 'dup-sku', 6 => 'DUP-2']),
            ]),
            'store_id' => $catalog['store']->id,
        ], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonPath('rows.0.errors.0', 'The SKU has already been taken.')
            ->assertJsonPath('rows.1.errors.0', 'The barcode has already been taken.')
            ->assertJsonPath('rows.2.errors.0', 'The SKU is used more than once in this file.')
            ->assertJsonPath('rows.3.errors.0', 'The SKU is used more than once in this file.');
    });

    it('accepts a file whose category, brand, and unit match ignoring case', function () {
        actingAsTenantUser(permissions: [Permission::ProductsCreate]);
        $catalog = batchProductCatalog();

        $this->post('/api/v1/products/batch/verify', [
            'file' => productBatchSpreadsheet([
                batchProductRow([2 => 'beverages', 3 => 'acme', 4 => 'piece']),
                batchProductRow([
                    0 => 'Water 1L',
                    1 => '',
                    5 => '',
                    6 => '',
                    9 => '',
                    10 => '2',
                ]),
            ]),
            'store_id' => $catalog['store']->id,
        ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('message', 'The spreadsheet is ready to upload.')
            ->assertJsonPath('valid', true)
            ->assertJsonPath('summary', ['total' => 2, 'valid' => 2, 'invalid' => 0])
            ->assertJsonPath('rows.0.status', 'ok')
            ->assertJsonPath('rows.1.status', 'ok')
            ->assertJsonPath('rows.1.data.sku', '')
            ->assertJsonPath('rows.1.data.quantity', '');

        expect(Product::query()->count())->toBe(0);
    });
});

describe('upload', function () {
    it('returns 422 and saves nothing when any row is invalid', function () {
        actingAsTenantUser(permissions: [Permission::ProductsCreate]);
        $catalog = batchProductCatalog();

        $this->post('/api/v1/products/batch', [
            'file' => productBatchSpreadsheet([
                batchProductRow(),
                batchProductRow([0 => '', 5 => 'WATER-1', 6 => '999']),
            ]),
            'store_id' => $catalog['store']->id,
        ], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonPath('valid', false)
            ->assertJsonPath('rows.0.status', 'ok')
            ->assertJsonPath('rows.1.status', 'error');

        expect(Product::query()->count())->toBe(0);
    });

    it('creates simple products and posts opening stock for a verified file', function () {
        $user = actingAsTenantUser(permissions: [Permission::ProductsCreate]);
        $catalog = batchProductCatalog();

        $response = $this->post('/api/v1/products/batch', [
            'file' => productBatchSpreadsheet([
                batchProductRow([2 => 'beverages', 3 => 'ACME', 4 => 'Piece']),
                batchProductRow([
                    0 => 'Water 1L',
                    1 => '',
                    5 => '',
                    6 => '',
                    7 => '4',
                    8 => '6',
                    9 => '',
                    10 => '2',
                ]),
            ]),
            'store_id' => $catalog['store']->id,
        ], ['Accept' => 'application/json']);

        $response->assertCreated()
            ->assertJsonPath('message', 'Products uploaded.')
            ->assertJsonPath('created', 2)
            ->assertJsonPath('data.0.name', 'Cola 500ml')
            ->assertJsonPath('data.0.product_type', 'simple')
            ->assertJsonPath('data.0.description', 'Sparkling drink')
            ->assertJsonPath('data.0.category_id', $catalog['category']->id)
            ->assertJsonPath('data.0.brand_id', $catalog['brand']->id)
            ->assertJsonPath('data.0.unit_id', $catalog['unit']->id)
            ->assertJsonPath('data.0.variants.0.sku', 'COLA-500')
            ->assertJsonPath('data.0.variants.0.barcode', '1234567890123')
            ->assertJsonPath('data.0.variants.0.barcode_type', 'CODE128')
            ->assertJsonPath('data.0.variants.0.cost_price', '10.0000')
            ->assertJsonPath('data.0.variants.0.selling_price', '15.0000')
            ->assertJsonPath('data.0.variants.0.compare_at_price', '5.0000')
            ->assertJsonPath('data.0.variants.0.quantity', '8.0000')
            ->assertJsonPath('data.0.variants.0.min_stock_level', 5)
            ->assertJsonPath('data.1.name', 'Water 1L')
            ->assertJsonPath('data.1.description', null);

        $cola = ProductVariant::query()->where('sku', 'COLA-500')->first();
        $water = ProductVariant::query()->where('product_id', $response->json('data.1.id'))->first();

        expect($cola)->not->toBeNull()
            ->and($water)->not->toBeNull()
            ->and($water->sku)->toStartWith('DASKU-')
            ->and($water->barcode)->toStartWith('DAPR')
            ->and($water->quantity)->toBe('0.0000')
            ->and($water->min_stock_level)->toBe(2)
            ->and(Product::query()->where('tenant_id', $user->tenant_id)->count())->toBe(2);

        $this->assertDatabaseHas('inventory_layers', [
            'product_variant_id' => $cola->id,
            'store_id' => $catalog['store']->id,
            'source_type' => 'opening_stock',
            'received_qty' => '8.0000',
            'unit_cost' => '10.0000',
            'status' => 'Open',
        ]);

        $this->assertDatabaseHas('stock_movements', [
            'product_variant_id' => $cola->id,
            'store_id' => $catalog['store']->id,
            'movement_type' => 'opening_stock',
            'quantity' => '8.0000',
            'direction' => 'In',
        ]);

        $this->assertDatabaseMissing('inventory_layers', [
            'product_variant_id' => $water->id,
        ]);
    });
});
