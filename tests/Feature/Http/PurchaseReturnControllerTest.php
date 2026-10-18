<?php

use App\Enums\Permission;
use App\Models\User;
use DA\Admin\Models\Tenant;
use DA\Inventory\Enums\PurchaseReturnRefundStatus;
use DA\Inventory\Enums\UnitType;
use DA\Inventory\Models\Brand;
use DA\Inventory\Models\Category;
use DA\Inventory\Models\InventoryBalance;
use DA\Inventory\Models\InventoryLayer;
use DA\Inventory\Models\ProductVariant;
use DA\Inventory\Models\Purchase;
use DA\Inventory\Models\PurchaseOrderItem;
use DA\Inventory\Models\PurchaseReturn;
use DA\Inventory\Models\PurchaseReturnAllocation;
use DA\Inventory\Models\StockMovement;
use DA\Inventory\Models\Store;
use DA\Inventory\Models\Supplier;
use DA\Inventory\Models\Unit;

if (! function_exists('inventoryProductCatalog')) {
    /**
     * @return array{category_id: int, brand_id: int, unit_id: int, store_id: int}
     */
    function inventoryProductCatalog(): array
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
}

if (! function_exists('receivedOpeningProduct')) {
    /**
     * @return array{store_id: int, supplier_id: int, variant_id: int, product_id: int}
     */
    function receivedOpeningProduct(int $quantity = 0, string $cost = '10.0000'): array
    {
        $catalog = inventoryProductCatalog();
        $supplier = Supplier::factory()->create();

        $created = test()->postJson('/api/v1/products', productPayload([
            ...$catalog,
            'variants' => [[
                'sku' => 'FIFO-'.fake()->unique()->numerify('####'),
                'barcode' => fake()->unique()->numerify('#############'),
                'barcode_type' => 'EAN',
                'cost_price' => $cost,
                'selling_price' => '15.0000',
                'quantity' => number_format($quantity, 4, '.', ''),
            ]],
        ]))->assertCreated();

        return [
            'store_id' => $catalog['store_id'],
            'supplier_id' => $supplier->id,
            'variant_id' => (int) $created->json('data.variants.0.id'),
            'product_id' => (int) $created->json('data.id'),
        ];
    }
}

if (! function_exists('receivePurchase')) {
    function receivePurchase(int $supplierId, int $storeId, int $variantId, string $quantity, string $unitCost): int
    {
        $purchaseId = test()->postJson('/api/v1/purchases', purchasePayload([
            'supplier_id' => $supplierId,
            'store_id' => $storeId,
            'discount_amount' => '0',
            'tax_amount' => '0',
            'items' => [[
                'product_variant_id' => $variantId,
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
            ]],
        ]))->assertCreated()->json('data.id');

        test()->putJson('/api/v1/purchases/'.$purchaseId.'/status', ['status' => 'Received'])
            ->assertOk();

        return (int) $purchaseId;
    }
}

/**
 * @return list<Permission>
 */
function purchaseReturnPermissions(): array
{
    return [
        Permission::ProductsCreate,
        Permission::PurchasingView,
        Permission::PurchasingCreate,
        Permission::PurchasingUpdate,
        Permission::PurchasingDelete,
        Permission::InventoryCreate,
        Permission::InventoryUpdate,
    ];
}

function purchaseReturnUser(): User
{
    return actingAsTenantUser(permissions: purchaseReturnPermissions());
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function purchaseReturnPayload(array $overrides = []): array
{
    return array_replace([
        'purchase_id' => 1,
        'return_date' => '2026-09-30',
        'discount_amount' => '0',
        'tax_amount' => '0',
        'reason' => 'Damaged',
        'notes' => 'Box was crushed',
        'items' => [[
            'purchase_order_item_id' => 1,
            'product_variant_id' => 1,
            'quantity' => '5',
            'unit_cost' => '80',
            'discount_amount' => '0',
            'tax_amount' => '0',
        ]],
    ], $overrides);
}

/**
 * @return array{purchase_id: int, purchase_order_item_id: int, variant_id: int, store_id: int, supplier_id: int, po_number: string}
 */
function receivedReturnPurchase(string $quantity = '20', string $unitCost = '100'): array
{
    $catalog = receivedOpeningProduct(0, $unitCost);
    $purchaseId = receivePurchase(
        $catalog['supplier_id'],
        $catalog['store_id'],
        $catalog['variant_id'],
        $quantity,
        $unitCost,
    );

    return [
        'purchase_id' => $purchaseId,
        'purchase_order_item_id' => (int) PurchaseOrderItem::query()->where('purchase_order_id', $purchaseId)->value('id'),
        'variant_id' => $catalog['variant_id'],
        'store_id' => $catalog['store_id'],
        'supplier_id' => $catalog['supplier_id'],
        'po_number' => (string) Purchase::query()->findOrFail($purchaseId)->po_number,
    ];
}

/**
 * @param  array{purchase_id: int, purchase_order_item_id: int, variant_id: int}  $purchase
 * @param  array<string, mixed>  $overrides
 */
function createPurchaseReturn(array $purchase, array $overrides = []): int
{
    return (int) test()->postJson('/api/v1/purchase-returns', purchaseReturnPayload(array_replace([
        'purchase_id' => $purchase['purchase_id'],
        'items' => [[
            'purchase_order_item_id' => $purchase['purchase_order_item_id'],
            'product_variant_id' => $purchase['variant_id'],
            'quantity' => '5',
            'unit_cost' => '80',
        ]],
    ], $overrides)))->assertCreated()->json('data.id');
}

describe('authentication and authorization', function () {
    it('returns 401 when no token is provided', function (string $method, string $uri) {
        $this->json($method, $uri)->assertUnauthorized();
    })->with([
        ['GET', '/api/v1/purchase-returns'],
        ['GET', '/api/v1/purchase-returns/summary'],
        ['GET', '/api/v1/purchase-returns/purchases/1/lines'],
        ['POST', '/api/v1/purchase-returns'],
        ['GET', '/api/v1/purchase-returns/1'],
        ['PUT', '/api/v1/purchase-returns/1'],
        ['PUT', '/api/v1/purchase-returns/1/status'],
        ['POST', '/api/v1/purchase-returns/delete'],
    ]);

    it('returns 403 when the user lacks the purchasing permission for the route', function () {
        actingAsTenantUser(permissions: [Permission::CategoriesView]);

        $this->getJson('/api/v1/purchase-returns')->assertForbidden();
        $this->getJson('/api/v1/purchase-returns/summary')->assertForbidden();
        $this->getJson('/api/v1/purchase-returns/purchases/1/lines')->assertForbidden();
        $this->postJson('/api/v1/purchase-returns', purchaseReturnPayload())->assertForbidden();
        $this->getJson('/api/v1/purchase-returns/1')->assertForbidden();
        $this->putJson('/api/v1/purchase-returns/1', purchaseReturnPayload())->assertForbidden();
        $this->putJson('/api/v1/purchase-returns/1/status', ['status' => 'Approved'])->assertForbidden();
        $this->postJson('/api/v1/purchase-returns/delete', ['action' => 'delete', 'ids' => [1]])->assertForbidden();
    });
});

describe('index and show', function () {
    it('lists purchase returns for the current tenant newest first', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::PurchasingView]);
        PurchaseReturn::factory()->create([
            'tenant_id' => $tenant->id,
            'return_number' => 'PR-OLDER',
        ]);
        PurchaseReturn::factory()->pending()->create([
            'tenant_id' => $tenant->id,
            'return_number' => 'PR-NEWER',
        ]);
        PurchaseReturn::withoutEvents(fn () => PurchaseReturn::factory()->create([
            'return_number' => 'PR-FOREIGN',
        ]));

        $this->getJson('/api/v1/purchase-returns')
            ->assertOk()
            ->assertJsonPath('data.0.return_number', 'PR-NEWER')
            ->assertJsonPath('data.0.status', 'Pending')
            ->assertJsonPath('data.1.return_number', 'PR-OLDER')
            ->assertJsonMissing(['return_number' => 'PR-FOREIGN']);
    });

    it('filters purchase returns by return number and ignores blank filters', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::PurchasingView]);
        PurchaseReturn::factory()->create(['tenant_id' => $tenant->id, 'return_number' => 'PR-MATCH', 'po_number' => 'PO-1']);
        PurchaseReturn::factory()->create(['tenant_id' => $tenant->id, 'return_number' => 'PR-OTHER', 'po_number' => 'PO-2']);

        $this->getJson('/api/v1/purchase-returns?limit=1&status=&refund_status=&search=PR-MATCH&supplier_id=&store_id=&purchase_id=&date_from=&date_to=')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.return_number', 'PR-MATCH')
            ->assertJsonPath('meta.per_page', 1);
    });

    it('returns 422 when the end date is before the start date', function () {
        actingAsTenantUser(permissions: [Permission::PurchasingView]);

        $this->getJson('/api/v1/purchase-returns?date_from=2026-09-30&date_to=2026-09-01')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('date_to');
    });

    it('returns 422 when the status filter is invalid', function () {
        actingAsTenantUser(permissions: [Permission::PurchasingView]);

        $this->getJson('/api/v1/purchase-returns?status=Shipped')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');
    });

    it('returns 404 when tenant A requests a tenant B purchase return', function () {
        actingAsTenantUser(permissions: [Permission::PurchasingView]);
        $foreign = PurchaseReturn::withoutEvents(fn () => PurchaseReturn::factory()->create());

        $this->getJson('/api/v1/purchase-returns/'.$foreign->id)->assertNotFound();
    });
});

describe('store and update', function () {
    it('creates a draft purchase return without moving stock', function () {
        $user = purchaseReturnUser();
        $purchase = receivedReturnPurchase();

        $this->postJson('/api/v1/purchase-returns', purchaseReturnPayload([
            'purchase_id' => $purchase['purchase_id'],
            'discount_amount' => '20',
            'tax_amount' => '8',
            'grand_total' => '1',
            'tenant_id' => 999,
            'refund_status' => 'Refunded',
            'store_id' => 999,
            'created_by' => 999,
            'items' => [[
                'purchase_order_item_id' => $purchase['purchase_order_item_id'],
                'product_variant_id' => $purchase['variant_id'],
                'quantity' => '5',
                'unit_cost' => '80',
                'discount_amount' => '10',
                'tax_amount' => '5',
            ]],
        ]))
            ->assertCreated()
            ->assertJsonPath('data.return_number', 'PR-'.now()->format('Ymd').'-0001')
            ->assertJsonPath('data.po_number', $purchase['po_number'])
            ->assertJsonPath('data.store_id', $purchase['store_id'])
            ->assertJsonPath('data.supplier_id', $purchase['supplier_id'])
            ->assertJsonPath('data.return_date', '2026-09-30')
            ->assertJsonPath('data.total_return_qty', '5.0000')
            ->assertJsonPath('data.subtotal', '400.0000')
            ->assertJsonPath('data.discount_amount', '20.0000')
            ->assertJsonPath('data.tax_amount', '8.0000')
            ->assertJsonPath('data.grand_total', '383.0000')
            ->assertJsonPath('data.status', 'Draft')
            ->assertJsonPath('data.refund_status', 'Pending')
            ->assertJsonPath('data.approval_by', null)
            ->assertJsonPath('data.created_by', $user->id)
            ->assertJsonPath('data.creator.name', $user->name)
            ->assertJsonPath('data.creator.email', $user->email)
            ->assertJsonPath('data.items.0.subtotal', '400.0000')
            ->assertJsonPath('data.items.0.total_amount', '395.0000')
            ->assertJsonPath('data.items.0.allocations', []);

        $layer = InventoryLayer::query()->where('source_id', $purchase['purchase_id'])->first();

        expect($layer->received_qty)->toBe('20.0000')
            ->and($layer->remaining_qty)->toBe('20.0000');

        $this->assertDatabaseMissing('stock_movements', [
            'movement_type' => 'purchase_return',
        ]);
    });

    it('returns 422 when the purchase has not been received', function () {
        purchaseReturnUser();
        $catalog = receivedOpeningProduct(0);
        $purchaseId = (int) $this->postJson('/api/v1/purchases', purchasePayload([
            'supplier_id' => $catalog['supplier_id'],
            'store_id' => $catalog['store_id'],
            'items' => [[
                'product_variant_id' => $catalog['variant_id'],
                'quantity' => '20',
                'unit_cost' => '100',
            ]],
        ]))->assertCreated()->json('data.id');
        $itemId = (int) PurchaseOrderItem::query()->where('purchase_order_id', $purchaseId)->value('id');

        $this->postJson('/api/v1/purchase-returns', purchaseReturnPayload([
            'purchase_id' => $purchaseId,
            'items' => [[
                'purchase_order_item_id' => $itemId,
                'product_variant_id' => $catalog['variant_id'],
                'quantity' => '1',
                'unit_cost' => '100',
            ]],
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['purchase_id' => 'Only a received purchase can be returned.']);
    });

    it('returns 422 when required fields are missing', function () {
        purchaseReturnUser();

        $this->postJson('/api/v1/purchase-returns', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'purchase_id' => 'The purchase field is required.',
                'return_date' => 'The return date field is required.',
                'items' => 'At least one purchase return item is required.',
            ]);
    });

    it('returns 422 when the return quantity exceeds the purchased quantity', function () {
        purchaseReturnUser();
        $purchase = receivedReturnPurchase();

        $this->postJson('/api/v1/purchase-returns', purchaseReturnPayload([
            'purchase_id' => $purchase['purchase_id'],
            'items' => [[
                'purchase_order_item_id' => $purchase['purchase_order_item_id'],
                'product_variant_id' => $purchase['variant_id'],
                'quantity' => '21',
                'unit_cost' => '80',
            ]],
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['items.0.quantity' => 'The return quantity cannot exceed the purchased quantity.']);
    });

    it('returns 422 when the variant does not match the purchase line', function () {
        purchaseReturnUser();
        $purchase = receivedReturnPurchase();
        $other = ProductVariant::factory()->create();

        $this->postJson('/api/v1/purchase-returns', purchaseReturnPayload([
            'purchase_id' => $purchase['purchase_id'],
            'items' => [[
                'purchase_order_item_id' => $purchase['purchase_order_item_id'],
                'product_variant_id' => $other->id,
                'quantity' => '1',
                'unit_cost' => '80',
            ]],
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['items.0.product_variant_id' => 'The product variant does not match the purchase line.']);
    });

    it('returns 422 when the line discount exceeds the line amount', function () {
        purchaseReturnUser();
        $purchase = receivedReturnPurchase();

        $this->postJson('/api/v1/purchase-returns', purchaseReturnPayload([
            'purchase_id' => $purchase['purchase_id'],
            'items' => [[
                'purchase_order_item_id' => $purchase['purchase_order_item_id'],
                'product_variant_id' => $purchase['variant_id'],
                'quantity' => '5',
                'unit_cost' => '80',
                'discount_amount' => '500',
            ]],
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['items.0.discount_amount' => 'The discount cannot exceed the line amount.']);
    });

    it('returns 422 when the grand total would be negative', function () {
        purchaseReturnUser();
        $purchase = receivedReturnPurchase();

        $this->postJson('/api/v1/purchase-returns', purchaseReturnPayload([
            'purchase_id' => $purchase['purchase_id'],
            'discount_amount' => '10000',
            'items' => [[
                'purchase_order_item_id' => $purchase['purchase_order_item_id'],
                'product_variant_id' => $purchase['variant_id'],
                'quantity' => '5',
                'unit_cost' => '80',
            ]],
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['discount_amount' => 'The grand total cannot be negative.']);
    });

    it('returns 422 when the purchase line is already on the return', function () {
        purchaseReturnUser();
        $purchase = receivedReturnPurchase();
        $line = [
            'purchase_order_item_id' => $purchase['purchase_order_item_id'],
            'product_variant_id' => $purchase['variant_id'],
            'quantity' => '1',
            'unit_cost' => '80',
        ];

        $this->postJson('/api/v1/purchase-returns', purchaseReturnPayload([
            'purchase_id' => $purchase['purchase_id'],
            'items' => [$line, $line],
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['items.1.purchase_order_item_id' => 'The purchase line is already on this return.']);
    });

    it('returns 422 when the purchase line belongs to another purchase', function () {
        purchaseReturnUser();
        $purchase = receivedReturnPurchase();
        $otherLine = PurchaseOrderItem::factory()->create([
            'product_variant_id' => $purchase['variant_id'],
        ]);

        $this->postJson('/api/v1/purchase-returns', purchaseReturnPayload([
            'purchase_id' => $purchase['purchase_id'],
            'items' => [[
                'purchase_order_item_id' => $otherLine->id,
                'product_variant_id' => $purchase['variant_id'],
                'quantity' => '1',
                'unit_cost' => '80',
            ]],
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['items.0.purchase_order_item_id' => 'The selected purchase line is invalid.']);
    });

    it('returns 422 when create status is approved', function () {
        purchaseReturnUser();
        $purchase = receivedReturnPurchase();

        $this->postJson('/api/v1/purchase-returns', purchaseReturnPayload([
            'purchase_id' => $purchase['purchase_id'],
            'status' => 'Approved',
            'items' => [[
                'purchase_order_item_id' => $purchase['purchase_order_item_id'],
                'product_variant_id' => $purchase['variant_id'],
                'quantity' => '1',
                'unit_cost' => '80',
            ]],
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status' => 'The selected status is invalid.']);
    });

    it('updates a pending purchase return until it is approved', function () {
        purchaseReturnUser();
        $purchase = receivedReturnPurchase();
        $created = $this->postJson('/api/v1/purchase-returns', purchaseReturnPayload([
            'purchase_id' => $purchase['purchase_id'],
            'status' => 'Pending',
            'items' => [[
                'purchase_order_item_id' => $purchase['purchase_order_item_id'],
                'product_variant_id' => $purchase['variant_id'],
                'quantity' => '5',
                'unit_cost' => '80',
            ]],
        ]))->assertCreated();

        $this->putJson('/api/v1/purchase-returns/'.$created->json('data.id'), purchaseReturnPayload([
            'purchase_id' => $purchase['purchase_id'],
            'status' => 'Pending',
            'items' => [[
                'id' => $created->json('data.items.0.id'),
                'purchase_order_item_id' => $purchase['purchase_order_item_id'],
                'product_variant_id' => $purchase['variant_id'],
                'quantity' => '2',
                'unit_cost' => '80',
            ]],
        ]))
            ->assertOk()
            ->assertJsonPath('data.total_return_qty', '2.0000')
            ->assertJsonPath('data.subtotal', '160.0000')
            ->assertJsonPath('data.grand_total', '160.0000')
            ->assertJsonPath('data.status', 'Pending')
            ->assertJsonPath('data.items.0.id', $created->json('data.items.0.id'));

        expect(InventoryLayer::query()->where('source_id', $purchase['purchase_id'])->first()->remaining_qty)->toBe('20.0000');
    });

    it('returns 404 when updating another tenant purchase return', function () {
        purchaseReturnUser();
        $purchase = receivedReturnPurchase();
        $foreign = PurchaseReturn::withoutEvents(fn () => PurchaseReturn::factory()->create());

        $this->putJson('/api/v1/purchase-returns/'.$foreign->id, purchaseReturnPayload([
            'purchase_id' => $purchase['purchase_id'],
            'items' => [[
                'purchase_order_item_id' => $purchase['purchase_order_item_id'],
                'product_variant_id' => $purchase['variant_id'],
                'quantity' => '1',
                'unit_cost' => '80',
            ]],
        ]))->assertNotFound();
    });
});

describe('approve and reject', function () {
    it('reduces remaining layer quantity and records the historical cost', function () {
        $user = purchaseReturnUser();
        $purchase = receivedReturnPurchase();
        $returnId = createPurchaseReturn($purchase);

        $this->putJson('/api/v1/purchase-returns/'.$returnId.'/status', ['status' => 'Approved'])
            ->assertOk()
            ->assertJsonPath('data.status', 'Approved')
            ->assertJsonPath('data.refund_status', 'Pending')
            ->assertJsonPath('data.approval_by', $user->id)
            ->assertJsonPath('data.items.0.allocations.0.quantity', '5.0000')
            ->assertJsonPath('data.items.0.allocations.0.unit_cost', '100.0000')
            ->assertJsonPath('data.items.0.allocations.0.total_cost', '500.0000')
            ->assertJsonPath('data.items.0.allocations.0.layer.received_qty', '20.0000')
            ->assertJsonPath('data.items.0.allocations.0.layer.remaining_qty', '15.0000');

        $layer = InventoryLayer::query()->where('source_id', $purchase['purchase_id'])->first();
        $returnItemId = (int) PurchaseReturn::query()->findOrFail($returnId)->items()->value('id');

        expect($layer->received_qty)->toBe('20.0000')
            ->and($layer->remaining_qty)->toBe('15.0000')
            ->and($layer->unit_cost)->toBe('100.0000')
            ->and($layer->total_cost)->toBe('2000.0000')
            ->and($layer->status->value)->toBe('Open');

        $this->assertDatabaseHas('purchase_return_allocations', [
            'purchase_return_item_id' => $returnItemId,
            'inventory_layer_id' => $layer->id,
            'quantity' => '5.0000',
            'unit_cost' => '100.0000',
            'total_cost' => '500.0000',
        ]);
        $this->assertDatabaseHas('stock_movements', [
            'movement_type' => 'purchase_return',
            'direction' => 'Out',
            'quantity' => '5.0000',
            'unit_cost' => '100.0000',
            'reference_type' => 'purchase_return',
            'reference_id' => $returnId,
            'reference_item_id' => $returnItemId,
            'inventory_layer_id' => $layer->id,
        ]);
        $this->assertDatabaseCount('purchase_return_allocations', 1);
        expect(StockMovement::query()->where('movement_type', 'purchase_return')->count())->toBe(1);

        $balance = InventoryBalance::query()->where('product_variant_id', $purchase['variant_id'])->first();

        expect($balance->quantity)->toBe('15.0000')
            ->and($balance->available_quantity)->toBe('15.0000')
            ->and($balance->stock_value)->toBe('1500.0000');
    });

    it('depletes the layer when the full remaining quantity is returned', function () {
        purchaseReturnUser();
        $purchase = receivedReturnPurchase();
        $returnId = createPurchaseReturn($purchase, [
            'items' => [[
                'purchase_order_item_id' => $purchase['purchase_order_item_id'],
                'product_variant_id' => $purchase['variant_id'],
                'quantity' => '20',
                'unit_cost' => '100',
            ]],
        ]);

        $this->putJson('/api/v1/purchase-returns/'.$returnId.'/status', ['status' => 'Approved'])
            ->assertOk()
            ->assertJsonPath('data.status', 'Approved');

        $layer = InventoryLayer::query()->where('source_id', $purchase['purchase_id'])->first();

        expect($layer->received_qty)->toBe('20.0000')
            ->and($layer->remaining_qty)->toBe('0.0000')
            ->and($layer->status->value)->toBe('Depleted');

        $balance = InventoryBalance::query()->where('product_variant_id', $purchase['variant_id'])->first();

        expect($balance->quantity)->toBe('0.0000')
            ->and($balance->stock_value)->toBe('0.0000');
    });

    it('does not post stock again when an approved return is approved twice', function () {
        purchaseReturnUser();
        $purchase = receivedReturnPurchase();
        $returnId = createPurchaseReturn($purchase);

        $this->putJson('/api/v1/purchase-returns/'.$returnId.'/status', ['status' => 'Approved'])->assertOk();
        $this->putJson('/api/v1/purchase-returns/'.$returnId.'/status', ['status' => 'Approved'])
            ->assertOk()
            ->assertJsonPath('data.status', 'Approved');

        expect(InventoryLayer::query()->where('source_id', $purchase['purchase_id'])->first()->remaining_qty)->toBe('15.0000')
            ->and(StockMovement::query()->where('movement_type', 'purchase_return')->count())->toBe(1)
            ->and(PurchaseReturnAllocation::query()->count())->toBe(1);
    });

    it('does not consume stock that belongs to a different purchase', function () {
        purchaseReturnUser();
        $catalog = receivedOpeningProduct(0, '100');
        $firstId = receivePurchase($catalog['supplier_id'], $catalog['store_id'], $catalog['variant_id'], '20', '100');
        $secondId = receivePurchase($catalog['supplier_id'], $catalog['store_id'], $catalog['variant_id'], '20', '200');
        $first = [
            'purchase_id' => $firstId,
            'purchase_order_item_id' => (int) PurchaseOrderItem::query()->where('purchase_order_id', $firstId)->value('id'),
            'variant_id' => $catalog['variant_id'],
        ];

        $this->postJson('/api/v1/stock-adjustments', [
            'store_id' => $catalog['store_id'],
            'product_variant_id' => $catalog['variant_id'],
            'adjustment_date' => '2026-09-30',
            'adjustment_type' => 'Subtraction',
            'reason' => 'Sold the first receipt',
            'adjusted_quantity' => '20',
            'status' => 'Completed',
        ])->assertCreated();

        $returnId = createPurchaseReturn($first, ['status' => 'Pending']);

        $this->putJson('/api/v1/purchase-returns/'.$returnId.'/status', ['status' => 'Approved'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['quantity' => 'This purchase does not have enough remaining stock to return.']);

        $this->assertDatabaseHas('purchase_returns', [
            'id' => $returnId,
            'status' => 'Pending',
        ]);
        $this->assertDatabaseCount('purchase_return_allocations', 0);

        $firstLayer = InventoryLayer::query()->where('source_id', $first['purchase_id'])->first();
        $secondLayer = InventoryLayer::query()->where('source_id', $secondId)->first();

        expect($firstLayer->received_qty)->toBe('20.0000')
            ->and($firstLayer->remaining_qty)->toBe('0.0000')
            ->and($secondLayer->received_qty)->toBe('20.0000')
            ->and($secondLayer->remaining_qty)->toBe('20.0000')
            ->and($secondLayer->unit_cost)->toBe('200.0000');
    });

    it('returns 422 and keeps the layer when remaining stock is short', function () {
        purchaseReturnUser();
        $purchase = receivedReturnPurchase();

        $this->postJson('/api/v1/stock-adjustments', [
            'store_id' => $purchase['store_id'],
            'product_variant_id' => $purchase['variant_id'],
            'adjustment_date' => '2026-09-30',
            'adjustment_type' => 'Subtraction',
            'reason' => 'Sold most of the receipt',
            'adjusted_quantity' => '4',
            'status' => 'Completed',
        ])->assertCreated();

        $returnId = createPurchaseReturn($purchase, ['status' => 'Pending']);

        $this->putJson('/api/v1/purchase-returns/'.$returnId.'/status', ['status' => 'Approved'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['quantity' => 'Only available stock can be returned.']);

        $layer = InventoryLayer::query()->where('source_id', $purchase['purchase_id'])->first();

        expect($layer->received_qty)->toBe('20.0000')
            ->and($layer->remaining_qty)->toBe('4.0000');
        $this->assertDatabaseHas('purchase_returns', ['id' => $returnId, 'status' => 'Pending']);
        $this->assertDatabaseCount('purchase_return_allocations', 0);
    });

    it('returns 422 when available stock is reserved', function () {
        purchaseReturnUser();
        $purchase = receivedReturnPurchase();
        $returnId = createPurchaseReturn($purchase, ['status' => 'Draft']);
        $balance = InventoryBalance::query()->where('product_variant_id', $purchase['variant_id'])->first();
        $balance->reserved_quantity = '18.0000';
        $balance->available_quantity = '2.0000';
        $balance->save();

        $this->putJson('/api/v1/purchase-returns/'.$returnId.'/status', ['status' => 'Approved'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['quantity' => 'Only available stock can be returned.']);

        expect(InventoryLayer::query()->where('source_id', $purchase['purchase_id'])->first()->remaining_qty)->toBe('20.0000');
        $this->assertDatabaseHas('purchase_returns', ['id' => $returnId, 'status' => 'Draft']);
        $this->assertDatabaseCount('purchase_return_allocations', 0);
    });

    it('rejects a pending return without moving stock', function () {
        $user = purchaseReturnUser();
        $purchase = receivedReturnPurchase();
        $returnId = createPurchaseReturn($purchase, ['status' => 'Pending']);

        $this->putJson('/api/v1/purchase-returns/'.$returnId.'/status', ['status' => 'Reject'])
            ->assertOk()
            ->assertJsonPath('data.status', 'Reject')
            ->assertJsonPath('data.approval_by', $user->id);

        expect($this->getJson('/api/v1/purchase-returns/'.$returnId)->json('data.approval_at'))->not->toBeNull();
        expect(InventoryLayer::query()->where('source_id', $purchase['purchase_id'])->first()->remaining_qty)->toBe('20.0000');
        $this->assertDatabaseMissing('stock_movements', ['movement_type' => 'purchase_return']);
    });

    it('returns 422 when an approved return is updated or rejected', function () {
        purchaseReturnUser();
        $purchase = receivedReturnPurchase();
        $returnId = createPurchaseReturn($purchase);
        $this->putJson('/api/v1/purchase-returns/'.$returnId.'/status', ['status' => 'Approved'])->assertOk();

        $this->putJson('/api/v1/purchase-returns/'.$returnId, purchaseReturnPayload([
            'purchase_id' => $purchase['purchase_id'],
            'items' => [[
                'purchase_order_item_id' => $purchase['purchase_order_item_id'],
                'product_variant_id' => $purchase['variant_id'],
                'quantity' => '1',
                'unit_cost' => '80',
            ]],
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status' => 'An approved purchase return cannot be updated.']);

        $this->putJson('/api/v1/purchase-returns/'.$returnId.'/status', ['status' => 'Reject'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status' => 'An approved purchase return cannot be rejected.']);

        expect(InventoryLayer::query()->where('source_id', $purchase['purchase_id'])->first()->remaining_qty)->toBe('15.0000');
    });

    it('returns 422 when a rejected return is approved or updated', function () {
        purchaseReturnUser();
        $purchase = receivedReturnPurchase();
        $returnId = createPurchaseReturn($purchase, ['status' => 'Pending']);
        $this->putJson('/api/v1/purchase-returns/'.$returnId.'/status', ['status' => 'Reject'])->assertOk();

        $this->putJson('/api/v1/purchase-returns/'.$returnId.'/status', ['status' => 'Approved'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status' => 'A rejected purchase return cannot be approved.']);

        $this->putJson('/api/v1/purchase-returns/'.$returnId, purchaseReturnPayload([
            'purchase_id' => $purchase['purchase_id'],
            'items' => [[
                'purchase_order_item_id' => $purchase['purchase_order_item_id'],
                'product_variant_id' => $purchase['variant_id'],
                'quantity' => '1',
                'unit_cost' => '80',
            ]],
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status' => 'A rejected purchase return cannot be updated.']);
    });

    it('returns 422 when the status transition is invalid', function () {
        purchaseReturnUser();
        $purchase = receivedReturnPurchase();
        $returnId = createPurchaseReturn($purchase);

        $this->putJson('/api/v1/purchase-returns/'.$returnId.'/status', ['status' => 'Draft'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status' => 'The selected status is invalid.']);
    });

    it('returns 404 when changing the status of another tenant purchase return', function () {
        actingAsTenantUser(permissions: [Permission::PurchasingUpdate]);
        $foreign = PurchaseReturn::withoutEvents(fn () => PurchaseReturn::factory()->create());

        $this->putJson('/api/v1/purchase-returns/'.$foreign->id.'/status', ['status' => 'Approved'])
            ->assertNotFound();
    });
});

describe('destroy', function () {
    it('deletes draft and pending purchase returns', function () {
        purchaseReturnUser();
        $purchase = receivedReturnPurchase();
        $draftId = createPurchaseReturn($purchase, ['status' => 'Draft']);
        $pendingId = createPurchaseReturn($purchase, [
            'status' => 'Pending',
            'items' => [[
                'purchase_order_item_id' => $purchase['purchase_order_item_id'],
                'product_variant_id' => $purchase['variant_id'],
                'quantity' => '1',
                'unit_cost' => '80',
            ]],
        ]);

        $this->postJson('/api/v1/purchase-returns/delete', [
            'action' => 'delete',
            'ids' => [$draftId, $pendingId],
        ])
            ->assertOk()
            ->assertJsonPath('message', 'Purchase return deleted successfully');

        $this->assertDatabaseMissing('purchase_returns', ['id' => $draftId]);
        $this->assertDatabaseMissing('purchase_returns', ['id' => $pendingId]);
        $this->assertDatabaseCount('purchase_return_items', 0);
    });

    it('returns 422 when an approved purchase return is deleted', function () {
        purchaseReturnUser();
        $purchase = receivedReturnPurchase();
        $returnId = createPurchaseReturn($purchase);
        $this->putJson('/api/v1/purchase-returns/'.$returnId.'/status', ['status' => 'Approved'])->assertOk();

        $this->postJson('/api/v1/purchase-returns/delete', [
            'action' => 'delete',
            'ids' => [$returnId],
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['ids' => 'Only a draft or pending purchase return can be deleted.']);

        $this->assertDatabaseHas('purchase_returns', ['id' => $returnId, 'status' => 'Approved']);
        expect(InventoryLayer::query()->where('source_id', $purchase['purchase_id'])->first()->remaining_qty)->toBe('15.0000');
    });

    it('returns 400 when the delete action is invalid', function () {
        actingAsTenantUser(permissions: [Permission::PurchasingDelete]);

        $this->postJson('/api/v1/purchase-returns/delete', ['action' => 'archive', 'ids' => [1]])
            ->assertBadRequest()
            ->assertJsonPath('message', 'Invalid action');
    });

    it('returns 404 when deleting another tenant purchase return', function () {
        actingAsTenantUser(permissions: [Permission::PurchasingDelete]);
        $foreign = PurchaseReturn::withoutEvents(fn () => PurchaseReturn::factory()->create());

        $this->postJson('/api/v1/purchase-returns/delete', [
            'action' => 'delete',
            'ids' => [$foreign->id],
        ])->assertNotFound();

        $this->assertDatabaseHas('purchase_returns', ['id' => $foreign->id]);
    });
});

describe('summary and returnable lines', function () {
    it('summarizes purchase returns for the current tenant', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::PurchasingView]);

        PurchaseReturn::factory()->create([
            'tenant_id' => $tenant->id,
            'grand_total' => '10.0000',
        ]);
        PurchaseReturn::factory()->pending()->create([
            'tenant_id' => $tenant->id,
            'grand_total' => '20.0000',
        ]);
        PurchaseReturn::factory()->approved()->create([
            'tenant_id' => $tenant->id,
            'grand_total' => '30.0000',
            'refund_status' => PurchaseReturnRefundStatus::Refunded,
        ]);
        PurchaseReturn::factory()->rejected()->create([
            'tenant_id' => $tenant->id,
            'grand_total' => '5.0000',
            'refund_status' => PurchaseReturnRefundStatus::Partial,
        ]);
        PurchaseReturn::withoutEvents(fn () => PurchaseReturn::factory()->create([
            'grand_total' => '999.0000',
        ]));

        $this->getJson('/api/v1/purchase-returns/summary')
            ->assertOk()
            ->assertJsonPath('data.total_returns', 4)
            ->assertJsonPath('data.total_amount', '65.0000')
            ->assertJsonPath('data.draft_count', 1)
            ->assertJsonPath('data.pending_count', 1)
            ->assertJsonPath('data.approved_count', 1)
            ->assertJsonPath('data.reject_count', 1)
            ->assertJsonPath('data.refunded_count', 1)
            ->assertJsonPath('data.refund_partial_count', 1)
            ->assertJsonPath('data.refund_pending_count', 2);
    });

    it('returns the remaining quantity on the purchase receipt layer', function () {
        purchaseReturnUser();
        $catalog = receivedOpeningProduct(0, '100');
        $purchaseId = (int) $this->postJson('/api/v1/purchases', purchasePayload([
            'supplier_id' => $catalog['supplier_id'],
            'store_id' => $catalog['store_id'],
            'discount_amount' => '0',
            'tax_amount' => '0',
            'items' => [[
                'product_variant_id' => $catalog['variant_id'],
                'quantity' => '20',
                'unit_cost' => '100',
                'discount_amount' => '0',
                'tax_amount' => '200',
            ]],
        ]))->assertCreated()->json('data.id');

        $this->putJson('/api/v1/purchases/'.$purchaseId.'/status', ['status' => 'Received'])->assertOk();

        $lineId = (int) PurchaseOrderItem::query()->where('purchase_order_id', $purchaseId)->value('id');

        $this->getJson('/api/v1/purchase-returns/purchases/'.$purchaseId.'/lines')
            ->assertOk()
            ->assertJsonPath('data.purchase.id', $purchaseId)
            ->assertJsonPath('data.purchase.store_id', $catalog['store_id'])
            ->assertJsonPath('data.items.0.purchase_order_item_id', $lineId)
            ->assertJsonPath('data.items.0.product_variant_id', $catalog['variant_id'])
            ->assertJsonPath('data.items.0.available_quantity', '20.0000')
            ->assertJsonPath('data.items.0.purchased_quantity', '20.0000')
            ->assertJsonPath('data.items.0.unit_cost', '100.0000')
            ->assertJsonPath('data.items.0.tax_amount', '200.0000')
            ->assertJsonPath('data.items.0.tax_rate', '10.00');

        $returnId = createPurchaseReturn([
            'purchase_id' => $purchaseId,
            'purchase_order_item_id' => $lineId,
            'variant_id' => $catalog['variant_id'],
        ]);

        $this->putJson('/api/v1/purchase-returns/'.$returnId.'/status', ['status' => 'Approved'])->assertOk();

        $this->getJson('/api/v1/purchase-returns/purchases/'.$purchaseId.'/lines')
            ->assertOk()
            ->assertJsonPath('data.items.0.available_quantity', '15.0000')
            ->assertJsonPath('data.items.0.purchased_quantity', '20.0000');
    });

    it('rejects return lines when the purchase is not received', function () {
        purchaseReturnUser();
        $catalog = receivedOpeningProduct();
        $purchaseId = (int) $this->postJson('/api/v1/purchases', purchasePayload([
            'supplier_id' => $catalog['supplier_id'],
            'store_id' => $catalog['store_id'],
            'items' => [[
                'product_variant_id' => $catalog['variant_id'],
                'quantity' => '2',
                'unit_cost' => '10',
            ]],
        ]))->assertCreated()->json('data.id');

        $this->getJson('/api/v1/purchase-returns/purchases/'.$purchaseId.'/lines')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['purchase_id' => 'Only a received purchase can be returned.']);
    });

    it('returns 404 for another tenant purchase lines', function () {
        purchaseReturnUser();
        $purchase = Purchase::withoutEvents(fn () => Purchase::factory()->create());

        $this->getJson('/api/v1/purchase-returns/purchases/'.$purchase->id.'/lines')->assertNotFound();
    });
});
