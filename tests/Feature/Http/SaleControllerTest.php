<?php

use App\Enums\Permission;
use DA\Inventory\Enums\UnitType;
use DA\Inventory\Models\Brand;
use DA\Inventory\Models\Category;
use DA\Inventory\Models\Customer;
use DA\Inventory\Models\InventoryBalance;
use DA\Inventory\Models\InventoryLayer;
use DA\Inventory\Models\StockMovement;
use DA\Inventory\Models\Store;
use DA\Inventory\Models\Supplier;
use DA\Inventory\Models\Unit;
use Laravel\Sanctum\Sanctum;

/**
 * @return list<Permission>
 */
function salePermissions(): array
{
    return [
        Permission::ProductsCreate,
        Permission::PurchasingCreate,
        Permission::PurchasingUpdate,
        Permission::SalesCreate,
        Permission::SalesView,
    ];
}

/**
 * @return array{category_id: int, brand_id: int, unit_id: int, store_id: int}
 */
function saleProductCatalog(): array
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

function saleReceivePurchase(int $supplierId, int $storeId, int $variantId, string $quantity, string $unitCost): void
{
    $purchaseId = test()->postJson('/api/v1/purchases', purchasePayload([
        'supplier_id' => $supplierId,
        'store_id' => $storeId,
        'discount_amount' => '0',
        'tax_amount' => '0',
        'shipping_amount' => '0',
        'items' => [[
            'product_variant_id' => $variantId,
            'quantity' => $quantity,
            'unit_cost' => $unitCost,
        ]],
    ]))->assertCreated()->json('data.id');

    test()->putJson('/api/v1/purchases/'.$purchaseId.'/status', ['status' => 'Received'])
        ->assertOk();
}

/**
 * @param  array<string, mixed>  $catalog
 */
function stockedVariant(array $catalog, string $name, string $quantity, string $cost = '10.0000', string $price = '15.0000'): int
{
    return (int) test()->postJson('/api/v1/products', productPayload([
        ...$catalog,
        'name' => $name,
        'variants' => [[
            'sku' => 'SALE-'.fake()->unique()->numerify('####'),
            'barcode' => fake()->unique()->numerify('#############'),
            'barcode_type' => 'EAN',
            'cost_price' => $cost,
            'selling_price' => $price,
            'quantity' => $quantity,
        ]],
    ]))->assertCreated()->json('data.variants.0.id');
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function salePayload(array $overrides = []): array
{
    return array_replace([
        'store_id' => 1,
        'sale_date' => '2026-10-08',
        'discount_amount' => '0',
        'tax_amount' => '0',
        'shipping_amount' => '0',
        'rounding_amount' => '0',
        'items' => [[
            'product_variant_id' => 1,
            'quantity' => '1',
            'unit_price' => '15.0000',
        ]],
        'payments' => [[
            'payment_method_id' => 1,
            'received_amount' => '15.0000',
        ]],
    ], $overrides);
}

describe('authentication and authorization', function () {
    it('returns 401 when no token is provided', function (string $method, string $uri) {
        $this->json($method, $uri, salePayload())->assertUnauthorized();
    })->with([
        ['GET', '/api/v1/sales'],
        ['POST', '/api/v1/sales'],
        ['GET', '/api/v1/sales/1'],
        ['POST', '/api/v1/sales/1/payments'],
    ]);

    it('returns 403 when the user cannot create a sale', function () {
        actingAsTenantUser(permissions: [Permission::SalesView]);

        $this->postJson('/api/v1/sales', salePayload())->assertForbidden();
    });
});

describe('checkout', function () {
    it('completes a sale, allocates stock, and returns the receipt', function () {
        $user = actingAsTenantUser(permissions: salePermissions());
        $catalog = saleProductCatalog();
        $variantId = stockedVariant($catalog, 'Cola Sale', '10.0000');
        $customer = Customer::factory()->create([
            'tenant_id' => $user->tenant_id,
            'name' => 'Nadia Rahman',
        ]);

        $created = $this->postJson('/api/v1/sales', salePayload([
            'store_id' => $catalog['store_id'],
            'customer_id' => $customer->id,
            'discount_amount' => '2',
            'tax_amount' => '1',
            'shipping_amount' => '0.5',
            'items' => [[
                'product_variant_id' => $variantId,
                'quantity' => '2',
                'unit_price' => '15.0000',
                'discount_amount' => '1',
            ]],
            'payments' => [[
                'payment_method_id' => 1,
                'received_amount' => '28.5000',
            ]],
        ]))->assertCreated()
            ->assertJsonPath('data.cashier_id', $user->id)
            ->assertJsonPath('data.customer_id', $customer->id)
            ->assertJsonPath('data.customer.name', 'Nadia Rahman')
            ->assertJsonPath('data.status', 'Completed')
            ->assertJsonPath('data.payment_status', 'Paid')
            ->assertJsonPath('data.total_items', '2.0000')
            ->assertJsonPath('data.subtotal', '30.0000')
            ->assertJsonPath('data.discount_amount', '2.0000')
            ->assertJsonPath('data.tax_amount', '1.0000')
            ->assertJsonPath('data.shipping_amount', '0.5000')
            ->assertJsonPath('data.grand_total', '28.5000')
            ->assertJsonPath('data.paid_amount', '28.5000')
            ->assertJsonPath('data.due_amount', '0.0000')
            ->assertJsonPath('data.items.0.product_variant_name', 'Cola Sale')
            ->assertJsonPath('data.items.0.subtotal', '30.0000')
            ->assertJsonPath('data.items.0.total', '29.0000')
            ->assertJsonPath('data.items.0.cost_price', '10.0000')
            ->assertJsonPath('data.items.0.cogs', '20.0000')
            ->assertJsonPath('data.payments.0.paid_amount', '28.5000')
            ->assertJsonPath('data.payments.0.returned_amount', '0.0000')
            ->assertJsonPath('data.payments.0.customer_id', $customer->id)
            ->assertJsonPath('data.payments.0.payment_method.name', 'Cash');

        $saleId = (int) $created->json('data.id');
        expect($created->json('data.invoice_number'))->toStartWith('INV-');

        $this->getJson('/api/v1/sales/'.$saleId)
            ->assertOk()
            ->assertJsonPath('data.invoice_number', $created->json('data.invoice_number'))
            ->assertJsonPath('data.grand_total', '28.5000');

        $balance = InventoryBalance::query()->where('product_variant_id', $variantId)->first();
        expect($balance)->not->toBeNull()
            ->and($balance->quantity)->toBe('8.0000')
            ->and($balance->available_quantity)->toBe('8.0000');

        $this->assertDatabaseHas('stock_movements', [
            'product_variant_id' => $variantId,
            'movement_type' => 'sale',
            'direction' => 'Out',
            'reference_type' => 'sale',
            'reference_id' => $saleId,
            'quantity' => '2.0000',
            'unit_cost' => '10.0000',
        ]);

        $this->assertDatabaseHas('inventory_allocations', [
            'source_type' => 'sale',
            'source_id' => $saleId,
            'product_variant_id' => $variantId,
            'quantity' => '2.0000',
            'total_cost' => '20.0000',
        ]);
    });

    it('stores fifo cost of goods from the oldest layers', function () {
        actingAsTenantUser(permissions: salePermissions());
        $catalog = saleProductCatalog();
        $variantId = stockedVariant($catalog, 'Fifo Cola', '2.0000', '10.0000');
        $supplier = Supplier::factory()->create();

        saleReceivePurchase($supplier->id, $catalog['store_id'], $variantId, '3', '16');

        $saleId = (int) $this->postJson('/api/v1/sales', salePayload([
            'store_id' => $catalog['store_id'],
            'items' => [[
                'product_variant_id' => $variantId,
                'quantity' => '4',
                'unit_price' => '15.0000',
            ]],
            'payments' => [[
                'payment_method_id' => 1,
                'received_amount' => '60',
            ]],
        ]))->assertCreated()
            ->assertJsonPath('data.items.0.cost_price', '13.0000')
            ->assertJsonPath('data.items.0.cogs', '52.0000')
            ->json('data.id');

        $this->assertDatabaseHas('inventory_layers', [
            'product_variant_id' => $variantId,
            'unit_cost' => '10.0000',
            'remaining_qty' => '0.0000',
            'status' => 'Depleted',
        ]);
        $this->assertDatabaseHas('inventory_layers', [
            'product_variant_id' => $variantId,
            'unit_cost' => '16.0000',
            'remaining_qty' => '1.0000',
            'status' => 'Open',
        ]);

        expect(InventoryBalance::query()->where('product_variant_id', $variantId)->value('quantity'))->toBe('1.0000')
            ->and($saleId)->toBeGreaterThan(0);
    });

    it('rejects a sale when stock is insufficient', function () {
        actingAsTenantUser(permissions: salePermissions());
        $catalog = saleProductCatalog();
        $variantId = stockedVariant($catalog, 'Short Cola', '1.0000');

        $this->postJson('/api/v1/sales', salePayload([
            'store_id' => $catalog['store_id'],
            'items' => [[
                'product_variant_id' => $variantId,
                'quantity' => '5',
                'unit_price' => '15.0000',
            ]],
            'payments' => [[
                'payment_method_id' => 1,
                'received_amount' => '75',
            ]],
        ]))->assertUnprocessable()
            ->assertJsonValidationErrors(['quantity' => 'Insufficient stock for this operation.']);

        $this->assertDatabaseCount('sales', 0);
        expect(InventoryBalance::query()->where('product_variant_id', $variantId)->value('quantity'))->toBe('1.0000');
    });

    it('rolls the sale back when fifo allocation fails', function () {
        actingAsTenantUser(permissions: salePermissions());
        $catalog = saleProductCatalog();
        $firstId = stockedVariant($catalog, 'First Cola', '5.0000');
        $secondId = stockedVariant($catalog, 'Second Cola', '1.0000');

        $this->postJson('/api/v1/sales', salePayload([
            'store_id' => $catalog['store_id'],
            'items' => [
                [
                    'product_variant_id' => $firstId,
                    'quantity' => '1',
                    'unit_price' => '15.0000',
                ],
                [
                    'product_variant_id' => $secondId,
                    'quantity' => '2',
                    'unit_price' => '15.0000',
                ],
            ],
            'payments' => [[
                'payment_method_id' => 1,
                'received_amount' => '45',
            ]],
        ]))->assertUnprocessable()
            ->assertJsonValidationErrors(['quantity' => 'Insufficient stock for this operation.']);

        $this->assertDatabaseCount('sales', 0);
        $this->assertDatabaseCount('sale_items', 0);
        $this->assertDatabaseCount('payments', 0);
        expect(StockMovement::query()->where('movement_type', 'sale')->count())->toBe(0)
            ->and(InventoryBalance::query()->where('product_variant_id', $firstId)->value('quantity'))->toBe('5.0000')
            ->and(InventoryBalance::query()->where('product_variant_id', $secondId)->value('quantity'))->toBe('1.0000');
    });

    it('sells an untracked product without a stock movement', function () {
        actingAsTenantUser(permissions: salePermissions());
        $catalog = saleProductCatalog();
        $variantId = (int) $this->postJson('/api/v1/products', productPayload([
            ...$catalog,
            'name' => 'Service Fee',
            'track_inventory' => false,
            'variants' => [[
                'sku' => 'FEE-'.fake()->unique()->numerify('####'),
                'barcode' => fake()->unique()->numerify('#############'),
                'barcode_type' => 'EAN',
                'cost_price' => '8.0000',
                'selling_price' => '15.0000',
            ]],
        ]))->assertCreated()->json('data.variants.0.id');

        $this->postJson('/api/v1/sales', salePayload([
            'store_id' => $catalog['store_id'],
            'items' => [[
                'product_variant_id' => $variantId,
                'quantity' => '1',
                'unit_price' => '15.0000',
            ]],
        ]))->assertCreated()
            ->assertJsonPath('data.items.0.cost_price', '8.0000')
            ->assertJsonPath('data.items.0.cogs', '8.0000');

        $this->assertDatabaseMissing('stock_movements', [
            'product_variant_id' => $variantId,
            'movement_type' => 'sale',
        ]);
    });
});

describe('payments', function () {
    it('records split payments and the change due back', function () {
        actingAsTenantUser(permissions: salePermissions());
        $catalog = saleProductCatalog();
        $variantId = stockedVariant($catalog, 'Split Cola', '2.0000');

        $this->postJson('/api/v1/sales', salePayload([
            'store_id' => $catalog['store_id'],
            'items' => [[
                'product_variant_id' => $variantId,
                'quantity' => '2',
                'unit_price' => '15.0000',
            ]],
            'payments' => [
                [
                    'payment_method_id' => 1,
                    'received_amount' => '10',
                ],
                [
                    'payment_method_id' => 2,
                    'received_amount' => '25',
                    'reference_number' => 'CARD-100',
                ],
            ],
        ]))->assertCreated()
            ->assertJsonPath('data.grand_total', '30.0000')
            ->assertJsonPath('data.paid_amount', '30.0000')
            ->assertJsonPath('data.due_amount', '0.0000')
            ->assertJsonPath('data.payment_status', 'Paid')
            ->assertJsonPath('data.payments.0.bill_amount', '30.0000')
            ->assertJsonPath('data.payments.0.paid_amount', '10.0000')
            ->assertJsonPath('data.payments.0.due_amount', '20.0000')
            ->assertJsonPath('data.payments.0.returned_amount', '0.0000')
            ->assertJsonPath('data.payments.1.bill_amount', '20.0000')
            ->assertJsonPath('data.payments.1.paid_amount', '20.0000')
            ->assertJsonPath('data.payments.1.due_amount', '0.0000')
            ->assertJsonPath('data.payments.1.returned_amount', '5.0000')
            ->assertJsonPath('data.payments.1.reference_number', 'CARD-100')
            ->assertJsonPath('data.payments.1.payment_method.name', 'Card');
    });

    it('keeps a partial sale due and accepts a later payment', function () {
        $user = actingAsTenantUser(permissions: salePermissions());
        $catalog = saleProductCatalog();
        $variantId = stockedVariant($catalog, 'Due Cola', '2.0000');

        $saleId = (int) $this->postJson('/api/v1/sales', salePayload([
            'store_id' => $catalog['store_id'],
            'items' => [[
                'product_variant_id' => $variantId,
                'quantity' => '2',
                'unit_price' => '15.0000',
            ]],
            'payments' => [[
                'payment_method_id' => 5,
                'received_amount' => '10',
            ]],
        ]))->assertCreated()
            ->assertJsonPath('data.payment_status', 'Partial')
            ->assertJsonPath('data.paid_amount', '10.0000')
            ->assertJsonPath('data.due_amount', '20.0000')
            ->json('data.id');

        $this->postJson('/api/v1/sales/'.$saleId.'/payments', [
            'payment_method_id' => 1,
            'received_amount' => '20',
        ])->assertCreated()
            ->assertJsonPath('data.payment_status', 'Paid')
            ->assertJsonPath('data.paid_amount', '30.0000')
            ->assertJsonPath('data.due_amount', '0.0000')
            ->assertJsonCount(2, 'data.payments')
            ->assertJsonPath('data.payments.1.received_by', $user->id);

        $this->postJson('/api/v1/sales/'.$saleId.'/payments', [
            'payment_method_id' => 1,
            'received_amount' => '5',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['received_amount' => 'This sale has no balance due.']);
    });

    it('records an unpaid sale when checkout receives nothing', function () {
        actingAsTenantUser(permissions: salePermissions());
        $catalog = saleProductCatalog();
        $variantId = stockedVariant($catalog, 'Unpaid Cola', '1.0000');

        $this->postJson('/api/v1/sales', salePayload([
            'store_id' => $catalog['store_id'],
            'items' => [[
                'product_variant_id' => $variantId,
                'quantity' => '1',
                'unit_price' => '15.0000',
            ]],
            'payments' => [[
                'payment_method_id' => 5,
                'received_amount' => '0',
            ]],
        ]))->assertCreated()
            ->assertJsonPath('data.payment_status', 'Unpaid')
            ->assertJsonPath('data.paid_amount', '0.0000')
            ->assertJsonPath('data.due_amount', '15.0000')
            ->assertJsonPath('data.payments.0.paid_amount', '0.0000')
            ->assertJsonPath('data.payments.0.payment_method.name', 'Due');
    });
});

describe('validation', function () {
    it('requires the store, items, and a payment', function () {
        actingAsTenantUser(permissions: salePermissions());

        $this->postJson('/api/v1/sales', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['store_id', 'items', 'payments']);
    });

    it('rejects a unit price that does not match the selling price', function () {
        actingAsTenantUser(permissions: salePermissions());
        $catalog = saleProductCatalog();
        $variantId = stockedVariant($catalog, 'Priced Cola', '5.0000');

        $this->postJson('/api/v1/sales', salePayload([
            'store_id' => $catalog['store_id'],
            'items' => [[
                'product_variant_id' => $variantId,
                'quantity' => '1',
                'unit_price' => '9.0000',
            ]],
        ]))->assertUnprocessable()
            ->assertJsonValidationErrors([
                'items.0.unit_price' => 'The unit price does not match the product selling price.',
            ]);
    });

    it('rejects a line discount above the line amount', function () {
        actingAsTenantUser(permissions: salePermissions());
        $catalog = saleProductCatalog();
        $variantId = stockedVariant($catalog, 'Discount Cola', '5.0000');

        $this->postJson('/api/v1/sales', salePayload([
            'store_id' => $catalog['store_id'],
            'items' => [[
                'product_variant_id' => $variantId,
                'quantity' => '1',
                'unit_price' => '15.0000',
                'discount_amount' => '20',
            ]],
        ]))->assertUnprocessable()
            ->assertJsonValidationErrors([
                'items.0.discount_amount' => 'The discount cannot exceed the line amount.',
            ]);
    });

    it('rejects a header discount that makes the grand total negative', function () {
        actingAsTenantUser(permissions: salePermissions());
        $catalog = saleProductCatalog();
        $variantId = stockedVariant($catalog, 'Header Discount Cola', '5.0000');

        $this->postJson('/api/v1/sales', salePayload([
            'store_id' => $catalog['store_id'],
            'discount_amount' => '100',
            'items' => [[
                'product_variant_id' => $variantId,
                'quantity' => '1',
                'unit_price' => '15.0000',
            ]],
        ]))->assertUnprocessable()
            ->assertJsonValidationErrors([
                'discount_amount' => 'The grand total cannot be negative.',
            ]);
    });
});

describe('tenant isolation', function () {
    it('does not reveal another tenant sale', function () {
        $owner = actingAsTenantUser(permissions: salePermissions());
        $catalog = saleProductCatalog();
        $variantId = stockedVariant($catalog, 'Owned Cola', '2.0000');
        $saleId = (int) $this->postJson('/api/v1/sales', salePayload([
            'store_id' => $catalog['store_id'],
            'items' => [[
                'product_variant_id' => $variantId,
                'quantity' => '1',
                'unit_price' => '15.0000',
            ]],
        ]))->assertCreated()->json('data.id');

        $this->getJson('/api/v1/sales')
            ->assertOk()
            ->assertJsonPath('data.0.id', $saleId);

        actingAsTenantUser(permissions: salePermissions());

        $this->getJson('/api/v1/sales')
            ->assertOk()
            ->assertJsonCount(0, 'data');
        $this->getJson('/api/v1/sales/'.$saleId)->assertNotFound();
        $this->postJson('/api/v1/sales/'.$saleId.'/payments', [
            'payment_method_id' => 1,
            'received_amount' => '5',
        ])->assertNotFound();

        expect($owner->tenant_id)->not->toBe(auth()->user()->tenant_id);
    });

    it('rejects another tenant store or product', function () {
        $owner = actingAsTenantUser(permissions: salePermissions());
        $catalog = saleProductCatalog();
        $variantId = stockedVariant($catalog, 'Local Cola', '2.0000');

        actingAsTenantUser(permissions: salePermissions());
        $foreignCatalog = saleProductCatalog();
        $foreignVariantId = stockedVariant($foreignCatalog, 'Foreign Cola', '2.0000');

        Sanctum::actingAs($owner);

        $this->postJson('/api/v1/sales', salePayload([
            'store_id' => $foreignCatalog['store_id'],
            'items' => [[
                'product_variant_id' => $variantId,
                'quantity' => '1',
                'unit_price' => '15.0000',
            ]],
        ]))->assertUnprocessable()
            ->assertJsonValidationErrors(['store_id' => 'The selected store is invalid.']);

        $this->postJson('/api/v1/sales', salePayload([
            'store_id' => $catalog['store_id'],
            'items' => [[
                'product_variant_id' => $foreignVariantId,
                'quantity' => '1',
                'unit_price' => '15.0000',
            ]],
        ]))->assertUnprocessable()
            ->assertJsonValidationErrors(['items.0.product_variant_id' => 'The selected product variant is invalid.']);
    });
});

describe('concurrent checkout', function () {
    it('does not oversell when two checkouts compete for the last unit', function () {
        actingAsTenantUser(permissions: salePermissions());
        $catalog = saleProductCatalog();
        $variantId = stockedVariant($catalog, 'Last Cola', '1.0000');
        $payload = salePayload([
            'store_id' => $catalog['store_id'],
            'items' => [[
                'product_variant_id' => $variantId,
                'quantity' => '1',
                'unit_price' => '15.0000',
            ]],
        ]);

        $this->postJson('/api/v1/sales', $payload)->assertCreated();
        $this->postJson('/api/v1/sales', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['quantity' => 'Insufficient stock for this operation.']);

        $this->assertDatabaseCount('sales', 1);
        expect(InventoryBalance::query()->where('product_variant_id', $variantId)->value('quantity'))->toBe('0.0000')
            ->and(InventoryLayer::query()->where('product_variant_id', $variantId)->value('remaining_qty'))->toBe('0.0000');
    });
});
