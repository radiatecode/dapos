<?php

use App\Enums\Permission;
use DA\Admin\Models\Tenant;
use DA\Inventory\Enums\ProductType;
use DA\Inventory\Enums\UnitType;
use DA\Inventory\Models\Attribute;
use DA\Inventory\Models\AttributeValue;
use DA\Inventory\Models\Brand;
use DA\Inventory\Models\Category;
use DA\Inventory\Models\InventoryBalance;
use DA\Inventory\Models\Product;
use DA\Inventory\Models\ProductVariant;
use DA\Inventory\Models\Store;
use DA\Inventory\Models\Tax;
use DA\Inventory\Models\Unit;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * @return array{category_id: int, brand_id: int, unit_id: int, store_id: int}
 */
function productCatalogIds(): array
{
    $unit = new Unit;
    $unit->name = 'Piece';
    $unit->code = 'PC'.fake()->unique()->numerify('###');
    $unit->unit_type = UnitType::Quantity;
    $unit->precision = 0;
    $unit->is_active = true;
    $unit->save();

    return [
        'category_id' => Category::factory()->create()->id,
        'brand_id' => Brand::factory()->create()->id,
        'unit_id' => $unit->id,
        'store_id' => Store::factory()->create()->id,
    ];
}

describe('authentication and authorization', function () {
    it('returns 401 when no token is provided', function (string $method, string $uri) {
        $this->json($method, $uri)->assertUnauthorized();
    })->with([
        ['GET', '/api/v1/products'],
        ['GET', '/api/v1/products/summary'],
        ['GET', '/api/v1/products/generate-sku'],
        ['GET', '/api/v1/products/generate-barcode'],
        ['POST', '/api/v1/products'],
        ['GET', '/api/v1/products/1'],
        ['PUT', '/api/v1/products/1'],
    ]);

    it('returns 403 when the user lacks the required permission', function () {
        actingAsTenantUser(permissions: [Permission::CategoriesView]);

        $this->getJson('/api/v1/products')->assertForbidden();
        $this->getJson('/api/v1/products/summary')->assertForbidden();
    });
});

describe('index and show', function () {
    it('lists products for the current tenant newest first', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::ProductsView]);
        $older = Product::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Older']);
        $newer = Product::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Newer']);
        $foreignTenant = Tenant::factory()->create();
        Product::withoutEvents(fn () => Product::factory()->create([
            'tenant_id' => $foreignTenant->id,
            'name' => 'Other tenant',
        ]));

        $this->getJson('/api/v1/products')
            ->assertOk()
            ->assertJsonPath('data.0.id', $newer->id)
            ->assertJsonPath('data.1.id', $older->id)
            ->assertJsonMissing(['name' => 'Other tenant']);
    });

    it('searches products by name', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::ProductsView]);
        $cola = Product::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Cola 500ml']);
        Product::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Orange juice']);

        $this->getJson('/api/v1/products?search=Cola')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $cola->id);
    });

    it('filters products by name', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::ProductsView]);
        $cola = Product::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Cola 500ml',
        ]);
        Product::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Orange juice',
        ]);

        $this->getJson('/api/v1/products?name=Cola')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $cola->id)
            ->assertJsonPath('data.0.name', 'Cola 500ml');
    });

    it('summarizes products for the current tenant', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::ProductsView]);
        Product::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Active one']);
        Product::factory()->inactive()->create(['tenant_id' => $tenant->id, 'name' => 'Inactive one']);
        $foreignTenant = Tenant::factory()->create();
        Product::withoutEvents(fn () => Product::factory()->create([
            'tenant_id' => $foreignTenant->id,
            'name' => 'Foreign product',
        ]));

        $this->getJson('/api/v1/products/summary')
            ->assertOk()
            ->assertJsonPath('data.total_products', 2)
            ->assertJsonPath('data.active_count', 1)
            ->assertJsonPath('data.inactive_count', 1);
    });

    it('filters products by category, brand, status, and sku', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::ProductsView]);
        $match = Product::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Match product']);
        ProductVariant::factory()->default()->create([
            'product_id' => $match->id,
            'tenant_id' => $tenant->id,
            'sku' => 'MATCH-SKU',
            'barcode' => '1112223334445',
        ]);
        Product::factory()->inactive()->create(['tenant_id' => $tenant->id, 'name' => 'Other product']);

        $this->getJson('/api/v1/products?search=MATCH-SKU&category_id='.$match->category_id.'&brand_id='.$match->brand_id.'&is_active=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $match->id);

        $this->getJson('/api/v1/products?search=&category_id=&brand_id=&is_active=')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    });

    it('searches products by barcode prefix', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::ProductsView]);
        $match = Product::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Scanned product']);
        ProductVariant::factory()->default()->create([
            'product_id' => $match->id,
            'tenant_id' => $tenant->id,
            'barcode' => '8801234567890',
        ]);
        Product::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Unrelated product']);

        $this->getJson('/api/v1/products?search=880123')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $match->id)
            ->assertJsonPath('data.0.default_variant.barcode', '8801234567890');
    });

    it('treats wildcard characters in the name filter literally', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::ProductsView]);
        $discounted = Product::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Cola 50% off']);
        Product::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Cola 500ml']);

        $this->getJson('/api/v1/products?name=50%25')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $discounted->id);
    });

    it('returns the selected store available quantity on the product list', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::ProductsView]);
        $product = Product::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Store stock']);
        $variant = ProductVariant::factory()->default()->create([
            'product_id' => $product->id,
            'tenant_id' => $tenant->id,
            'quantity' => '20.0000',
        ]);
        $store = Store::factory()->create(['tenant_id' => $tenant->id]);
        InventoryBalance::factory()->create([
            'tenant_id' => $tenant->id,
            'store_id' => $store->id,
            'product_variant_id' => $variant->id,
            'quantity' => '4.0000',
            'available_quantity' => '4.0000',
        ]);

        $this->getJson('/api/v1/products?store_id='.$store->id)
            ->assertOk()
            ->assertJsonPath('data.0.default_variant.quantity', '4.0000')
            ->assertJsonPath('data.0.variants.0.quantity', '4.0000');
    });

    it('keeps the catalog quantity when no store is selected', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::ProductsView]);
        $product = Product::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Catalog stock']);
        $variant = ProductVariant::factory()->default()->create([
            'product_id' => $product->id,
            'tenant_id' => $tenant->id,
            'quantity' => '20.0000',
        ]);
        $store = Store::factory()->create(['tenant_id' => $tenant->id]);
        InventoryBalance::factory()->create([
            'tenant_id' => $tenant->id,
            'store_id' => $store->id,
            'product_variant_id' => $variant->id,
            'available_quantity' => '4.0000',
        ]);

        $this->getJson('/api/v1/products')
            ->assertOk()
            ->assertJsonPath('data.0.default_variant.quantity', '20.0000');
    });

    it('returns zero quantity when the selected store has no balance', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::ProductsView]);
        $product = Product::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Empty store']);
        ProductVariant::factory()->default()->create([
            'product_id' => $product->id,
            'tenant_id' => $tenant->id,
            'quantity' => '20.0000',
        ]);
        $store = Store::factory()->create(['tenant_id' => $tenant->id]);

        $this->getJson('/api/v1/products?store_id='.$store->id)
            ->assertOk()
            ->assertJsonPath('data.0.default_variant.quantity', '0.0000')
            ->assertJsonPath('data.0.variants.0.quantity', '0.0000');
    });

    it('returns 422 when the store belongs to another tenant', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::ProductsView]);
        $foreignTenant = Tenant::factory()->create();
        $foreignStore = Store::withoutEvents(fn () => Store::factory()->create([
            'tenant_id' => $foreignTenant->id,
        ]));

        $this->getJson('/api/v1/products?store_id='.$foreignStore->id)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['store_id' => 'The selected store is invalid.']);
    });

    it('returns the product with its default variant', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::ProductsView]);
        $product = Product::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Shown',
        ]);
        ProductVariant::factory()->default()->create([
            'product_id' => $product->id,
            'tenant_id' => $tenant->id,
            'sku' => 'SHOWN-1',
        ]);

        $this->getJson('/api/v1/products/'.$product->id)
            ->assertOk()
            ->assertJsonPath('data.name', 'Shown')
            ->assertJsonPath('data.variants.0.sku', 'SHOWN-1')
            ->assertJsonPath('data.variants.0.is_default', true);
    });

    it('returns the selected store available quantity for a product', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::ProductsView]);
        $product = Product::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Shown stock']);
        $variant = ProductVariant::factory()->default()->create([
            'product_id' => $product->id,
            'tenant_id' => $tenant->id,
            'quantity' => '20.0000',
        ]);
        $store = Store::factory()->create(['tenant_id' => $tenant->id]);
        InventoryBalance::factory()->create([
            'tenant_id' => $tenant->id,
            'store_id' => $store->id,
            'product_variant_id' => $variant->id,
            'available_quantity' => '7.0000',
        ]);

        $this->getJson('/api/v1/products/'.$product->id.'?store_id='.$store->id)
            ->assertOk()
            ->assertJsonPath('data.variants.0.quantity', '7.0000')
            ->assertJsonPath('data.default_variant.quantity', '7.0000');
    });

    it('returns 404 when tenant A requests a tenant B product', function () {
        $tenantA = Tenant::factory()->create();
        actingAsTenantUser($tenantA, [Permission::ProductsView]);
        $foreign = Product::withoutEvents(fn () => Product::factory()->create());

        $this->getJson('/api/v1/products/'.$foreign->id)->assertNotFound();
    });
});

describe('store', function () {
    it('creates a simple product as one default variant', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::ProductsCreate]);

        $this->postJson('/api/v1/products', productPayload([
            ...productCatalogIds(),
            'manufacture_date' => '2026-01-15',
            'expire_date' => '2026-12-31',
            'warranty_in_days' => 365,
            'guarantee_in_days' => 90,
        ]))
            ->assertCreated()
            ->assertJsonPath('data.name', 'Cola 500ml')
            ->assertJsonPath('data.slug', 'cola-500ml')
            ->assertJsonPath('data.product_type', ProductType::Simple->value)
            ->assertJsonPath('data.is_active', true)
            ->assertJsonPath('data.track_inventory', true)
            ->assertJsonPath('data.manufacture_date', '2026-01-15')
            ->assertJsonPath('data.expire_date', '2026-12-31')
            ->assertJsonPath('data.warranty_in_days', 365)
            ->assertJsonPath('data.guarantee_in_days', 90)
            ->assertJsonPath('data.variants.0.sku', 'COLA-500')
            ->assertJsonPath('data.variants.0.barcode', '1234567890123')
            ->assertJsonPath('data.variants.0.barcode_type', 'EAN')
            ->assertJsonPath('data.variants.0.is_default', true)
            ->assertJsonPath('data.variants.0.selling_price', '15.0000')
            ->assertJsonPath('data.variants.0.compare_at_price', '5.0000')
            ->assertJsonPath('data.variants.0.quantity', '20.0000')
            ->assertJsonCount(1, 'data.variants');

        $this->assertDatabaseHas('products', [
            'name' => 'Cola 500ml',
            'slug' => 'cola-500ml',
            'tenant_id' => $tenant->id,
            'guarantee_in_days' => 90,
        ]);
        $this->assertDatabaseHas('product_variants', [
            'tenant_id' => $tenant->id,
            'sku' => 'COLA-500',
            'barcode' => '1234567890123',
            'is_default' => true,
        ]);
        $this->assertDatabaseCount('product_attributes', 0);
    });

    it('generates a datetime sku for the current tenant', function () {
        actingAsTenantUser(permissions: [Permission::ProductsCreate]);

        $sku = $this->getJson('/api/v1/products/generate-sku')
            ->assertOk()
            ->json('sku');

        expect($sku)->toBeString()->toStartWith('DASKU-');
    });

    it('returns 403 when generating a sku without product permissions', function () {
        actingAsTenantUser(permissions: [Permission::CategoriesView]);

        $this->getJson('/api/v1/products/generate-sku')->assertForbidden();
    });

    it('generates the next sequential barcode for the current tenant', function () {
        actingAsTenantUser(permissions: [Permission::ProductsCreate]);

        $this->getJson('/api/v1/products/generate-barcode')
            ->assertOk()
            ->assertJsonPath('barcode', 'DAPR0000001');
    });

    it('skips reserved barcodes when generating the next barcode', function () {
        actingAsTenantUser(permissions: [Permission::ProductsCreate]);

        $this->getJson('/api/v1/products/generate-barcode?reserved[]=DAPR0000001')
            ->assertOk()
            ->assertJsonPath('barcode', 'DAPR0000002');
    });

    it('returns 403 when generating a barcode without product permissions', function () {
        actingAsTenantUser(permissions: [Permission::CategoriesView]);

        $this->getJson('/api/v1/products/generate-barcode')->assertForbidden();
    });

    it('creates variable variants with sequential barcodes when barcode is omitted', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::ProductsCreate]);
        $color = Attribute::factory()->create(['name' => 'Color']);
        $red = AttributeValue::factory()->create(['attribute_id' => $color->id, 'value' => 'Red']);
        $blue = AttributeValue::factory()->create(['attribute_id' => $color->id, 'value' => 'Blue']);

        $this->postJson('/api/v1/products', productPayload([
            ...productCatalogIds(),
            'name' => 'T Shirt',
            'product_type' => 'variable',
            'attributes' => [
                ['attribute_id' => $color->id],
            ],
            'variants' => [
                [
                    'sku' => 'SHIRT-RED',
                    'selling_price' => '25.0000',
                    'cost_price' => '10.0000',
                    'quantity' => '4.0000',
                    'is_default' => true,
                    'attribute_values' => [
                        ['attribute_id' => $color->id, 'attribute_value_id' => $red->id],
                    ],
                ],
                [
                    'sku' => 'SHIRT-BLUE',
                    'selling_price' => '25.0000',
                    'cost_price' => '10.0000',
                    'quantity' => '6.0000',
                    'attribute_values' => [
                        ['attribute_id' => $color->id, 'attribute_value_id' => $blue->id],
                    ],
                ],
            ],
        ]))
            ->assertCreated()
            ->assertJsonPath('data.variants.0.barcode', 'DAPR0000001')
            ->assertJsonPath('data.variants.0.barcode_type', 'CODE128')
            ->assertJsonPath('data.variants.1.barcode', 'DAPR0000002')
            ->assertJsonPath('data.variants.1.barcode_type', 'CODE128');
    });

    it('overwrites a submitted compare price with the cost and selling difference', function () {
        actingAsTenantUser(permissions: [Permission::ProductsCreate]);

        $this->postJson('/api/v1/products', productPayload([
            ...productCatalogIds(),
            'variants' => [
                [
                    'sku' => 'COLA-500',
                    'barcode' => '1234567890123',
                    'barcode_type' => 'EAN',
                    'cost_price' => '8.0000',
                    'selling_price' => '20.0000',
                    'compare_at_price' => '99.0000',
                    'quantity' => '1.0000',
                ],
            ],
        ]))
            ->assertCreated()
            ->assertJsonPath('data.variants.0.compare_at_price', '12.0000');
    });

    it('creates a variable product with variants for each attribute combination', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::ProductsCreate]);
        $color = Attribute::factory()->create(['name' => 'Color']);
        $red = AttributeValue::factory()->create(['attribute_id' => $color->id, 'value' => 'Red']);
        $blue = AttributeValue::factory()->create(['attribute_id' => $color->id, 'value' => 'Blue']);

        $this->postJson('/api/v1/products', productPayload([
            ...productCatalogIds(),
            'name' => 'T Shirt',
            'product_type' => 'variable',
            'attributes' => [
                ['attribute_id' => $color->id, 'sort_order' => 1, 'is_required' => true],
            ],
            'variants' => [
                [
                    'sku' => 'SHIRT-RED',
                    'barcode' => '1000000000001',
                    'barcode_type' => 'CODE128',
                    'cost_price' => '10.0000',
                    'selling_price' => '25.0000',
                    'quantity' => '4.0000',
                    'is_default' => true,
                    'attribute_values' => [
                        ['attribute_id' => $color->id, 'attribute_value_id' => $red->id],
                    ],
                ],
                [
                    'sku' => 'SHIRT-BLUE',
                    'barcode' => '1000000000002',
                    'barcode_type' => 'CODE128',
                    'cost_price' => '10.0000',
                    'selling_price' => '25.0000',
                    'quantity' => '6.0000',
                    'attribute_values' => [
                        ['attribute_id' => $color->id, 'attribute_value_id' => $blue->id],
                    ],
                ],
            ],
        ]))
            ->assertCreated()
            ->assertJsonPath('data.product_type', ProductType::Variable->value)
            ->assertJsonPath('data.attributes.0.attribute_id', $color->id)
            ->assertJsonPath('data.attributes.0.is_required', true)
            ->assertJsonPath('data.variants.0.sku', 'SHIRT-RED')
            ->assertJsonPath('data.variants.0.is_default', true)
            ->assertJsonPath('data.variants.0.attribute_values.0.attribute_value_id', $red->id)
            ->assertJsonPath('data.variants.1.sku', 'SHIRT-BLUE')
            ->assertJsonCount(2, 'data.variants');

        $this->assertDatabaseCount('variant_attribute_values', 2);
    });

    it('stores an inactive product without inventory quantities', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::ProductsCreate]);

        $this->postJson('/api/v1/products', productPayload([
            ...productCatalogIds(),
            'is_active' => false,
            'track_inventory' => false,
            'variants' => [
                [
                    'sku' => 'COLA-500',
                    'barcode' => '1234567890123',
                    'barcode_type' => 'INTERNAL',
                    'is_active' => false,
                ],
            ],
        ]))
            ->assertCreated()
            ->assertJsonPath('data.is_active', false)
            ->assertJsonPath('data.track_inventory', false)
            ->assertJsonPath('data.variants.0.is_active', false)
            ->assertJsonPath('data.variants.0.quantity', null)
            ->assertJsonPath('data.variants.0.min_stock_level', null);
    });

    it('stores the product and variant images', function () {
        Storage::fake('public');
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::ProductsCreate]);
        $image = UploadedFile::fake()->image('cola.jpg');
        $variantImage = UploadedFile::fake()->image('cola-variant.jpg');

        $response = $this->post('/api/v1/products', productPayload([
            ...productCatalogIds(),
            'image' => $image,
            'variants' => [
                [
                    'sku' => 'COLA-500',
                    'barcode' => '1234567890123',
                    'barcode_type' => 'EAN',
                    'cost_price' => '10.0000',
                    'selling_price' => '15.0000',
                    'quantity' => '20.0000',
                    'image' => $variantImage,
                ],
            ],
        ]), ['Accept' => 'application/json']);

        $response->assertCreated();

        $productImage = $response->json('data.image');
        $storedVariantImage = $response->json('data.variants.0.image');

        expect($productImage)->toBeString()->and($storedVariantImage)->toBeString();
        Storage::disk('public')->assertExists($productImage);
        Storage::disk('public')->assertExists($storedVariantImage);
    });

    it('returns 422 when required fields are missing', function () {
        actingAsTenantUser(permissions: [Permission::ProductsCreate]);

        $this->postJson('/api/v1/products', [])
            ->assertUnprocessable()
            ->assertJsonPath('errors.name.0', 'The name field is required.')
            ->assertJsonValidationErrors([
                'name',
                'product_type',
                'category_id',
                'brand_id',
                'unit_id',
                'variants',
            ]);
    });

    it('returns 422 when the slug is already taken', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::ProductsCreate]);
        Product::factory()->create([
            'tenant_id' => $tenant->id,
            'slug' => 'cola-500ml',
        ]);

        $this->postJson('/api/v1/products', productPayload(productCatalogIds()))
            ->assertUnprocessable()
            ->assertJsonPath('errors.slug.0', 'The slug has already been taken.');
    });

    it('allows the same slug for another tenant', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::ProductsCreate]);
        $foreignTenant = Tenant::factory()->create();
        Product::withoutEvents(fn () => Product::factory()->create([
            'tenant_id' => $foreignTenant->id,
            'slug' => 'cola-500ml',
            'name' => 'Foreign cola',
        ]));

        $this->postJson('/api/v1/products', productPayload(productCatalogIds()))
            ->assertCreated()
            ->assertJsonPath('data.slug', 'cola-500ml');
    });

    it('returns 422 when a variant code is already taken', function (string $field, string $message) {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::ProductsCreate]);
        $catalog = productCatalogIds();
        $this->postJson('/api/v1/products', productPayload($catalog))->assertCreated();

        $this->postJson('/api/v1/products', productPayload([
            ...$catalog,
            'name' => 'Other cola',
            'variants' => [
                [
                    'sku' => $field === 'sku' ? 'COLA-500' : 'COLA-501',
                    'barcode' => $field === 'barcode' ? '1234567890123' : '1234567890124',
                    'barcode_type' => 'EAN',
                    'quantity' => '1.0000',
                ],
            ],
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                "variants.0.$field" => $message,
            ]);
    })->with([
        ['sku', 'The SKU has already been taken.'],
        ['barcode', 'The barcode has already been taken.'],
    ]);

    it('returns 422 when a simple product includes more than one variant', function () {
        actingAsTenantUser(permissions: [Permission::ProductsCreate]);

        $this->postJson('/api/v1/products', productPayload([
            ...productCatalogIds(),
            'variants' => [
                [
                    'sku' => 'COLA-500',
                    'barcode' => '1234567890123',
                    'barcode_type' => 'EAN',
                    'quantity' => '1.0000',
                ],
                [
                    'sku' => 'COLA-501',
                    'barcode' => '1234567890124',
                    'barcode_type' => 'EAN',
                    'quantity' => '1.0000',
                ],
            ],
        ]))
            ->assertUnprocessable()
            ->assertJsonPath('errors.variants.0', 'A simple product must have exactly one variant.');
    });

    it('stores min stock when inventory tracking is disabled', function () {
        actingAsTenantUser(permissions: [Permission::ProductsCreate]);

        $this->postJson('/api/v1/products', productPayload([
            ...productCatalogIds(),
            'track_inventory' => false,
            'variants' => [
                [
                    'sku' => 'COLA-500',
                    'barcode' => '1234567890123',
                    'barcode_type' => 'EAN',
                    'min_stock_level' => 4,
                ],
            ],
        ]))
            ->assertCreated()
            ->assertJsonPath('data.track_inventory', false)
            ->assertJsonPath('data.variants.0.quantity', null)
            ->assertJsonPath('data.variants.0.min_stock_level', 4);
    });

    it('returns 422 when quantity is sent without inventory tracking', function () {
        actingAsTenantUser(permissions: [Permission::ProductsCreate]);

        $this->postJson('/api/v1/products', productPayload([
            ...productCatalogIds(),
            'track_inventory' => false,
            'variants' => [
                [
                    'sku' => 'COLA-500',
                    'barcode' => '1234567890123',
                    'barcode_type' => 'EAN',
                    'quantity' => '5.0000',
                ],
            ],
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'variants.0.quantity' => 'Quantity is only allowed when inventory tracking is enabled.',
            ]);
    });

    it('returns 422 when quantity is missing while inventory tracking is enabled', function () {
        actingAsTenantUser(permissions: [Permission::ProductsCreate]);

        $this->postJson('/api/v1/products', productPayload([
            ...productCatalogIds(),
            'variants' => [
                [
                    'sku' => 'COLA-500',
                    'barcode' => '1234567890123',
                    'barcode_type' => 'EAN',
                ],
            ],
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'variants.0.quantity' => 'The quantity field is required when inventory tracking is enabled.',
            ]);
    });

    it('returns 422 when the category belongs to another tenant', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::ProductsCreate]);
        $foreignCategory = Category::withoutEvents(fn () => Category::factory()->create([
            'tenant_id' => Tenant::factory()->create()->id,
        ]));

        $this->postJson('/api/v1/products', productPayload([
            ...productCatalogIds(),
            'category_id' => $foreignCategory->id,
        ]))
            ->assertUnprocessable()
            ->assertJsonPath('errors.category_id.0', 'The selected category is invalid.');
    });

    it('returns 422 when the expire date is before the manufacture date', function () {
        actingAsTenantUser(permissions: [Permission::ProductsCreate]);

        $this->postJson('/api/v1/products', productPayload([
            ...productCatalogIds(),
            'manufacture_date' => '2026-12-31',
            'expire_date' => '2026-01-01',
        ]))
            ->assertUnprocessable()
            ->assertJsonPath('errors.expire_date.0', 'The expire date must be a date after or equal to the manufacture date.');
    });

    it('returns 422 when variants repeat an attribute combination', function () {
        actingAsTenantUser(permissions: [Permission::ProductsCreate]);
        $color = Attribute::factory()->create(['name' => 'Color']);
        $red = AttributeValue::factory()->create(['attribute_id' => $color->id, 'value' => 'Red']);

        $this->postJson('/api/v1/products', productPayload([
            ...productCatalogIds(),
            'name' => 'T Shirt',
            'product_type' => 'variable',
            'attributes' => [
                ['attribute_id' => $color->id],
            ],
            'variants' => [
                [
                    'sku' => 'SHIRT-RED',
                    'barcode' => '1000000000001',
                    'barcode_type' => 'UPC',
                    'quantity' => '1.0000',
                    'attribute_values' => [
                        ['attribute_id' => $color->id, 'attribute_value_id' => $red->id],
                    ],
                ],
                [
                    'sku' => 'SHIRT-RED-2',
                    'barcode' => '1000000000002',
                    'barcode_type' => 'UPC',
                    'quantity' => '1.0000',
                    'attribute_values' => [
                        ['attribute_id' => $color->id, 'attribute_value_id' => $red->id],
                    ],
                ],
            ],
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'variants.1.attribute_values' => 'Each variant must use a different combination of attribute values.',
            ]);
    });

    it('returns 422 when an attribute value does not belong to the attribute', function () {
        actingAsTenantUser(permissions: [Permission::ProductsCreate]);
        $color = Attribute::factory()->create(['name' => 'Color']);
        $size = Attribute::factory()->create(['name' => 'Size']);
        $small = AttributeValue::factory()->create(['attribute_id' => $size->id, 'value' => 'Small']);

        $this->postJson('/api/v1/products', productPayload([
            ...productCatalogIds(),
            'name' => 'T Shirt',
            'product_type' => 'variable',
            'attributes' => [
                ['attribute_id' => $color->id],
            ],
            'variants' => [
                [
                    'sku' => 'SHIRT-RED',
                    'barcode' => '1000000000001',
                    'barcode_type' => 'UPC',
                    'quantity' => '1.0000',
                    'attribute_values' => [
                        ['attribute_id' => $color->id, 'attribute_value_id' => $small->id],
                    ],
                ],
            ],
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'variants.0.attribute_values.0.attribute_value_id' => 'The selected attribute value is invalid.',
            ]);
    });
});

describe('update', function () {
    it('updates a simple product and its variant', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::ProductsCreate, Permission::ProductsUpdate]);
        $catalog = productCatalogIds();
        $created = $this->postJson('/api/v1/products', productPayload($catalog))->assertCreated();
        $variantId = $created->json('data.variants.0.id');

        $this->putJson('/api/v1/products/'.$created->json('data.id'), productPayload([
            ...$catalog,
            'name' => 'Cola 1L',
            'variants' => [
                [
                    'id' => $variantId,
                    'sku' => 'COLA-1000',
                    'barcode' => '1234567890999',
                    'barcode_type' => 'EAN',
                    'cost_price' => '10.0000',
                    'selling_price' => '20.0000',
                    'quantity' => '20.0000',
                ],
            ],
        ]))
            ->assertOk()
            ->assertJsonPath('data.name', 'Cola 1L')
            ->assertJsonPath('data.slug', 'cola-1l')
            ->assertJsonPath('data.variants.0.id', $variantId)
            ->assertJsonPath('data.variants.0.sku', 'COLA-1000')
            ->assertJsonPath('data.variants.0.selling_price', '20.0000')
            ->assertJsonPath('data.variants.0.is_default', true);

        $this->assertDatabaseHas('product_variants', [
            'id' => $variantId,
            'sku' => 'COLA-1000',
            'barcode' => '1234567890999',
        ]);
    });

    it('adds, updates, and soft deletes variants on a variable product', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::ProductsCreate, Permission::ProductsUpdate]);
        $color = Attribute::factory()->create(['name' => 'Color']);
        $red = AttributeValue::factory()->create(['attribute_id' => $color->id, 'value' => 'Red']);
        $blue = AttributeValue::factory()->create(['attribute_id' => $color->id, 'value' => 'Blue']);
        $green = AttributeValue::factory()->create(['attribute_id' => $color->id, 'value' => 'Green']);
        $catalog = productCatalogIds();

        $created = $this->postJson('/api/v1/products', productPayload([
            ...$catalog,
            'name' => 'T Shirt',
            'product_type' => 'variable',
            'attributes' => [
                ['attribute_id' => $color->id],
            ],
            'variants' => [
                [
                    'sku' => 'SHIRT-RED',
                    'barcode' => '1000000000001',
                    'barcode_type' => 'CODE128',
                    'quantity' => '0.0000',
                    'selling_price' => '25.0000',
                    'is_default' => true,
                    'attribute_values' => [
                        ['attribute_id' => $color->id, 'attribute_value_id' => $red->id],
                    ],
                ],
                [
                    'sku' => 'SHIRT-BLUE',
                    'barcode' => '1000000000002',
                    'barcode_type' => 'CODE128',
                    'quantity' => '0.0000',
                    'attribute_values' => [
                        ['attribute_id' => $color->id, 'attribute_value_id' => $blue->id],
                    ],
                ],
            ],
        ]))->assertCreated();

        $productId = $created->json('data.id');
        $keptId = $created->json('data.variants.0.id');
        $removedId = $created->json('data.variants.1.id');

        $this->putJson('/api/v1/products/'.$productId, productPayload([
            ...$catalog,
            'name' => 'T Shirt',
            'product_type' => 'variable',
            'attributes' => [
                ['attribute_id' => $color->id],
            ],
            'variants' => [
                [
                    'id' => $keptId,
                    'sku' => 'SHIRT-RED',
                    'barcode' => '1000000000001',
                    'barcode_type' => 'CODE128',
                    'cost_price' => '10.0000',
                    'quantity' => '9.0000',
                    'selling_price' => '27.0000',
                    'is_default' => true,
                    'attribute_values' => [
                        ['attribute_id' => $color->id, 'attribute_value_id' => $red->id],
                    ],
                ],
                [
                    'sku' => 'SHIRT-GREEN',
                    'barcode' => '1000000000003',
                    'barcode_type' => 'CODE128',
                    'cost_price' => '10.0000',
                    'quantity' => '3.0000',
                    'attribute_values' => [
                        ['attribute_id' => $color->id, 'attribute_value_id' => $green->id],
                    ],
                ],
            ],
        ]))
            ->assertOk()
            ->assertJsonPath('data.variants.0.id', $keptId)
            ->assertJsonPath('data.variants.0.selling_price', '27.0000')
            ->assertJsonPath('data.variants.1.sku', 'SHIRT-GREEN')
            ->assertJsonMissing(['sku' => 'SHIRT-BLUE']);

        $this->assertSoftDeleted('product_variants', ['id' => $removedId]);
        $this->assertDatabaseHas('product_variants', [
            'product_id' => $productId,
            'sku' => 'SHIRT-GREEN',
            'deleted_at' => null,
        ]);
    });

    it('returns 404 when updating another tenants product', function () {
        $tenantA = Tenant::factory()->create();
        actingAsTenantUser($tenantA, [Permission::ProductsUpdate]);
        $foreign = Product::withoutEvents(fn () => Product::factory()->create());

        $this->putJson('/api/v1/products/'.$foreign->id, productPayload(productCatalogIds()))
            ->assertNotFound();
    });
});

describe('product taxes', function () {
    it('stores the selected active taxes on the product', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::ProductsCreate, Permission::ProductsView]);
        $vat = Tax::factory()->create([
            'tenant_id' => $tenant->id,
            'tax_name' => 'VAT',
            'tax_rate' => 15,
        ]);
        $fee = Tax::factory()->fixed()->create([
            'tenant_id' => $tenant->id,
            'tax_name' => 'Bottle fee',
            'tax_rate' => 2,
        ]);

        $response = $this->postJson('/api/v1/products', productPayload([
            ...productCatalogIds(),
            'tax_ids' => [$fee->id, $vat->id],
            'item_tax_type' => 1,
        ]))->assertCreated();

        $taxIds = collect([$vat->id, $fee->id])->sort()->values()->all();

        $response
            ->assertJsonPath('data.tax_ids', $taxIds)
            ->assertJsonPath('data.item_tax_type', 1)
            ->assertJsonPath('data.item_tax_type_label', 'Inclusive Tax')
            ->assertJsonCount(2, 'data.taxes');

        $this->assertDatabaseHas('product_taxes', [
            'tenant_id' => $tenant->id,
            'product_id' => $response->json('data.id'),
            'tax_id' => $vat->id,
        ]);
        $this->assertDatabaseHas('product_taxes', [
            'tenant_id' => $tenant->id,
            'product_id' => $response->json('data.id'),
            'tax_id' => $fee->id,
        ]);

        $this->getJson('/api/v1/products/'.$response->json('data.id'))
            ->assertOk()
            ->assertJsonPath('data.tax_ids', $taxIds);

        $this->getJson('/api/v1/products')
            ->assertOk()
            ->assertJsonPath('data.0.tax_ids', $taxIds)
            ->assertJsonCount(2, 'data.0.taxes');
    });

    it('returns 422 when a tax belongs to another tenant', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::ProductsCreate]);
        $foreignTenant = Tenant::factory()->create();
        $foreignTax = Tax::withoutEvents(fn () => Tax::factory()->create([
            'tenant_id' => $foreignTenant->id,
        ]));

        $this->postJson('/api/v1/products', productPayload([
            ...productCatalogIds(),
            'tax_ids' => [$foreignTax->id],
            'item_tax_type' => 2,
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['tax_ids.0']);

        $this->assertDatabaseCount('product_taxes', 0);
    });

    it('returns 422 when a tax is inactive', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::ProductsCreate]);
        $inactive = Tax::factory()->inactive()->create([
            'tenant_id' => $tenant->id,
        ]);

        $this->postJson('/api/v1/products', productPayload([
            ...productCatalogIds(),
            'tax_ids' => [$inactive->id],
            'item_tax_type' => 2,
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['tax_ids.0']);
    });

    it('replaces product taxes and clears them when none are sent', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::ProductsCreate, Permission::ProductsUpdate]);
        $catalog = productCatalogIds();
        $vat = Tax::factory()->create(['tenant_id' => $tenant->id, 'tax_name' => 'VAT']);
        $service = Tax::factory()->create(['tenant_id' => $tenant->id, 'tax_name' => 'Service']);
        $created = $this->postJson('/api/v1/products', productPayload([
            ...$catalog,
            'tax_ids' => [$vat->id, $service->id],
            'item_tax_type' => 2,
        ]))->assertCreated();
        $productId = $created->json('data.id');
        $variantId = $created->json('data.variants.0.id');

        $this->putJson('/api/v1/products/'.$productId, productPayload([
            ...$catalog,
            'tax_ids' => [$service->id],
            'item_tax_type' => 1,
            'variants' => [
                [
                    'id' => $variantId,
                    'sku' => 'COLA-500',
                    'barcode' => '1234567890123',
                    'barcode_type' => 'EAN',
                    'cost_price' => '10.0000',
                    'selling_price' => '15.0000',
                    'quantity' => '20.0000',
                ],
            ],
        ]))
            ->assertOk()
            ->assertJsonPath('data.tax_ids', [$service->id])
            ->assertJsonPath('data.item_tax_type', 1);

        $this->assertDatabaseMissing('product_taxes', [
            'product_id' => $productId,
            'tax_id' => $vat->id,
        ]);
        $this->assertDatabaseHas('product_taxes', [
            'product_id' => $productId,
            'tax_id' => $service->id,
        ]);

        $this->putJson('/api/v1/products/'.$productId, productPayload([
            ...$catalog,
            'tax_ids' => [],
            'variants' => [
                [
                    'id' => $variantId,
                    'sku' => 'COLA-500',
                    'barcode' => '1234567890123',
                    'barcode_type' => 'EAN',
                    'cost_price' => '10.0000',
                    'selling_price' => '15.0000',
                    'quantity' => '20.0000',
                ],
            ],
        ]))
            ->assertOk()
            ->assertJsonPath('data.tax_ids', []);

        $this->assertDatabaseMissing('product_taxes', [
            'product_id' => $productId,
        ]);
        $this->assertDatabaseHas('products', [
            'id' => $productId,
            'item_tax_type' => 2,
        ]);
    });

    it('returns 422 when taxes are selected without an item tax type', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::ProductsCreate]);
        $vat = Tax::factory()->create(['tenant_id' => $tenant->id]);

        $this->postJson('/api/v1/products', productPayload([
            ...productCatalogIds(),
            'tax_ids' => [$vat->id],
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['item_tax_type']);
    });

    it('stores exclusive tax when the product has no taxes', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::ProductsCreate]);

        $this->postJson('/api/v1/products', productPayload([
            ...productCatalogIds(),
            'item_tax_type' => 1,
        ]))
            ->assertCreated()
            ->assertJsonPath('data.item_tax_type', 2)
            ->assertJsonPath('data.tax_ids', []);
    });
});
