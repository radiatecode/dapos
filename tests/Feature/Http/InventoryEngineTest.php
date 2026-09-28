<?php

use App\Enums\Permission;
use DA\Admin\Models\Tenant;
use DA\Inventory\Enums\InventoryLayerStatus;
use DA\Inventory\Enums\StockDirection;
use DA\Inventory\Enums\UnitType;
use DA\Inventory\Models\Brand;
use DA\Inventory\Models\Category;
use DA\Inventory\Models\InventoryAllocation;
use DA\Inventory\Models\InventoryBalance;
use DA\Inventory\Models\InventoryLayer;
use DA\Inventory\Models\StockAdjustment;
use DA\Inventory\Models\StockMovement;
use DA\Inventory\Models\Store;
use DA\Inventory\Models\Supplier;
use DA\Inventory\Models\Unit;

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

function receivePurchase(int $supplierId, int $storeId, int $variantId, string $quantity, string $unitCost): int
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

    return (int) $purchaseId;
}

describe('inventory read api', function () {
    it('returns 401 when no token is provided', function () {
        $this->getJson('/api/v1/inventory')->assertUnauthorized();
        $this->getJson('/api/v1/inventory-movements')->assertUnauthorized();
        $this->getJson('/api/v1/inventory-layers')->assertUnauthorized();
        $this->getJson('/api/v1/stock-adjustments')->assertUnauthorized();
        $this->getJson('/api/v1/stock-transfers')->assertUnauthorized();
    });

    it('returns 403 without inventory view permission', function () {
        actingAsTenantUser(permissions: [Permission::ProductsView]);

        $this->getJson('/api/v1/inventory')->assertForbidden();
    });

    it('accepts a limit and ignores blank read filters', function () {
        actingAsTenantUser(permissions: [Permission::InventoryView]);

        $this->getJson('/api/v1/inventory?limit=5&store_id=&product_id=')
            ->assertOk()
            ->assertJsonPath('meta.per_page', 5);

        $this->getJson('/api/v1/inventory-movements?limit=5&direction=&movement_type=&date_from=')
            ->assertOk()
            ->assertJsonPath('meta.per_page', 5);

        $this->getJson('/api/v1/inventory-layers?limit=5&status=&source_type=')
            ->assertOk()
            ->assertJsonPath('meta.per_page', 5);
    });

    it('accepts a limit and ignores a blank adjustment filter', function () {
        actingAsTenantUser(permissions: [Permission::InventoryView]);

        $this->getJson('/api/v1/stock-adjustments?limit=5&status=&store_id=')
            ->assertOk()
            ->assertJsonPath('meta.per_page', 5);
    });
});

describe('opening stock and purchase receipt', function () {
    it('posts opening stock when a tracked product is created', function () {
        actingAsTenantUser(permissions: [Permission::ProductsCreate, Permission::InventoryView]);
        $catalog = receivedOpeningProduct(20, '10.0000');

        $balance = InventoryBalance::query()->where('product_variant_id', $catalog['variant_id'])->first();

        expect($balance)->not->toBeNull()
            ->and($balance->quantity)->toBe('20.0000')
            ->and($balance->available_quantity)->toBe('20.0000')
            ->and($balance->stock_value)->toBe('200.0000');

        $this->assertDatabaseHas('inventory_layers', [
            'product_variant_id' => $catalog['variant_id'],
            'store_id' => $catalog['store_id'],
            'source_type' => 'opening_stock',
            'source_id' => $catalog['product_id'],
            'source_item_id' => $catalog['variant_id'],
            'received_qty' => '20.0000',
            'unit_cost' => '10.0000',
            'status' => 'Open',
        ]);

        $this->getJson('/api/v1/inventory?product_id='.$catalog['product_id'])
            ->assertOk()
            ->assertJsonPath('data.0.product_variant_id', $catalog['variant_id'])
            ->assertJsonPath('data.0.quantity', '20.0000')
            ->assertJsonPath('data.0.variant.product.id', $catalog['product_id']);
    });

    it('requires a store when inventory tracking is enabled', function () {
        actingAsTenantUser(permissions: [Permission::ProductsCreate]);
        $catalog = inventoryProductCatalog();
        unset($catalog['store_id']);

        $this->postJson('/api/v1/products', productPayload($catalog))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['store_id' => 'The store field is required when inventory tracking is enabled.']);
    });

    it('rejects a quantity change after opening stock exists', function () {
        actingAsTenantUser(permissions: [Permission::ProductsCreate, Permission::ProductsUpdate]);
        $catalog = inventoryProductCatalog();
        $created = $this->postJson('/api/v1/products', productPayload($catalog))->assertCreated();

        $this->putJson('/api/v1/products/'.$created->json('data.id'), productPayload([
            ...$catalog,
            'variants' => [[
                'id' => $created->json('data.variants.0.id'),
                'sku' => 'COLA-500',
                'barcode' => '1234567890123',
                'barcode_type' => 'EAN',
                'cost_price' => '10.0000',
                'selling_price' => '15.0000',
                'quantity' => '5.0000',
            ]],
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'variants.0.quantity' => 'The quantity cannot be changed after stock has been received. Use a stock adjustment.',
            ]);
    });

    it('posts purchase receipt once and keeps a repeated receive from doubling stock', function () {
        actingAsTenantUser(permissions: [
            Permission::ProductsCreate,
            Permission::PurchasingCreate,
            Permission::PurchasingUpdate,
        ]);
        $catalog = receivedOpeningProduct(0);

        $purchaseId = receivePurchase($catalog['supplier_id'], $catalog['store_id'], $catalog['variant_id'], '4', '8');

        $this->putJson('/api/v1/purchases/'.$purchaseId.'/status', ['status' => 'Received'])
            ->assertOk()
            ->assertJsonPath('data.status', 'Received');

        expect(InventoryLayer::query()->where('source_id', $purchaseId)->count())->toBe(1)
            ->and(StockMovement::query()->where('reference_id', $purchaseId)->count())->toBe(1)
            ->and(InventoryBalance::query()->where('product_variant_id', $catalog['variant_id'])->first()->quantity)->toBe('4.0000');

        $foreign = Tenant::factory()->create();

        actingAsTenantUser($foreign, [Permission::PurchasingView, Permission::PurchasingUpdate]);
        $this->getJson('/api/v1/purchases/'.$purchaseId)->assertNotFound();
        $this->putJson('/api/v1/purchases/'.$purchaseId.'/status', ['status' => 'Received'])
            ->assertNotFound();
    });
});

describe('fifo adjustments and transfers', function () {
    it('consumes layers in fifo order and leaves the newest cost', function () {
        actingAsTenantUser(permissions: [
            Permission::ProductsCreate,
            Permission::PurchasingCreate,
            Permission::PurchasingUpdate,
            Permission::InventoryCreate,
            Permission::InventoryUpdate,
            Permission::InventoryView,
        ]);
        $catalog = receivedOpeningProduct(0);
        receivePurchase($catalog['supplier_id'], $catalog['store_id'], $catalog['variant_id'], '100', '5');
        receivePurchase($catalog['supplier_id'], $catalog['store_id'], $catalog['variant_id'], '100', '7');
        receivePurchase($catalog['supplier_id'], $catalog['store_id'], $catalog['variant_id'], '100', '10');

        $adjustment = $this->postJson('/api/v1/stock-adjustments', [
            'store_id' => $catalog['store_id'],
            'product_variant_id' => $catalog['variant_id'],
            'adjustment_date' => '2026-09-27',
            'adjustment_type' => 'Subtraction',
            'reason' => 'Sold through fifo',
            'adjusted_quantity' => '50',
            'status' => 'Completed',
        ])->assertCreated()
            ->assertJsonPath('data.status', 'Completed')
            ->assertJsonPath('data.system_quantity', '300.0000')
            ->assertJsonPath('data.difference_quantity', '-250.0000')
            ->assertJsonPath('data.total_cost', '1700.0000')
            ->assertJsonPath('data.reference_number', 'ADJ-000001');

        $layers = InventoryLayer::query()
            ->where('product_variant_id', $catalog['variant_id'])
            ->orderBy('id')
            ->get();

        expect($layers)->toHaveCount(3)
            ->and($layers[0]->remaining_qty)->toBe('0.0000')
            ->and($layers[0]->status)->toBe(InventoryLayerStatus::Depleted)
            ->and($layers[1]->remaining_qty)->toBe('0.0000')
            ->and($layers[2]->remaining_qty)->toBe('50.0000')
            ->and($layers[2]->unit_cost)->toBe('10.0000');

        $allocations = InventoryAllocation::query()->orderBy('id')->get();
        expect($allocations)->toHaveCount(3)
            ->and($allocations[0]->quantity)->toBe('100.0000')
            ->and($allocations[0]->total_cost)->toBe('500.0000')
            ->and($allocations[1]->total_cost)->toBe('700.0000')
            ->and($allocations[2]->quantity)->toBe('50.0000')
            ->and($allocations[2]->total_cost)->toBe('500.0000');

        expect(StockMovement::query()->where('direction', StockDirection::Out)->count())->toBe(3);

        $balance = InventoryBalance::query()->where('product_variant_id', $catalog['variant_id'])->first();
        expect($balance->quantity)->toBe('50.0000')
            ->and($balance->stock_value)->toBe('500.0000');

        $this->getJson('/api/v1/inventory-layers?product_variant_id='.$catalog['variant_id'])
            ->assertOk()
            ->assertJsonCount(3, 'data');

        $this->getJson('/api/v1/inventory-movements?direction=Out')
            ->assertOk()
            ->assertJsonCount(3, 'data');

        $this->putJson('/api/v1/stock-adjustments/'.$adjustment->json('data.id'), [
            'store_id' => $catalog['store_id'],
            'product_variant_id' => $catalog['variant_id'],
            'adjustment_date' => '2026-09-27',
            'adjustment_type' => 'Subtraction',
            'reason' => 'Change after post',
            'adjusted_quantity' => '40',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['status' => 'Only a draft adjustment can be updated.']);
    });

    it('does not move stock for a draft and adds a layer at the supplied cost', function () {
        actingAsTenantUser(permissions: [
            Permission::ProductsCreate,
            Permission::InventoryCreate,
            Permission::InventoryUpdate,
        ]);
        $catalog = receivedOpeningProduct(0);

        $draft = $this->postJson('/api/v1/stock-adjustments', [
            'store_id' => $catalog['store_id'],
            'product_variant_id' => $catalog['variant_id'],
            'adjustment_date' => '2026-09-27',
            'adjustment_type' => 'Addition',
            'reason' => 'Found stock',
            'adjusted_quantity' => '2',
            'unit_cost' => '12',
        ])->assertCreated()
            ->assertJsonPath('data.status', 'Draft');

        expect(InventoryLayer::query()->count())->toBe(0);

        $this->putJson('/api/v1/stock-adjustments/'.$draft->json('data.id').'/status', [
            'status' => 'Completed',
        ])->assertOk()
            ->assertJsonPath('data.difference_quantity', '2.0000')
            ->assertJsonPath('data.unit_cost', '12.0000')
            ->assertJsonPath('data.total_cost', '24.0000');

        $this->assertDatabaseHas('inventory_layers', [
            'source_type' => 'stock_adjustment',
            'source_id' => $draft->json('data.id'),
            'received_qty' => '2.0000',
            'unit_cost' => '12.0000',
            'remaining_qty' => '2.0000',
        ]);
    });

    it('rolls back a transfer that asks for more stock than is available', function () {
        actingAsTenantUser(permissions: [
            Permission::ProductsCreate,
            Permission::InventoryCreate,
            Permission::InventoryUpdate,
        ]);
        $catalog = receivedOpeningProduct(10, '8.0000');
        $destination = Store::factory()->create();

        $transfer = $this->postJson('/api/v1/stock-transfers', [
            'product_variant_id' => $catalog['variant_id'],
            'from_store_id' => $catalog['store_id'],
            'to_store_id' => $destination->id,
            'transfer_date' => '2026-09-27',
            'quantity' => '11',
        ])->assertCreated()
            ->assertJsonPath('data.status', 'Draft');

        $this->putJson('/api/v1/stock-transfers/'.$transfer->json('data.id').'/status', [
            'status' => 'Completed',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['quantity' => 'Insufficient stock for this operation.']);

        expect(StockMovement::query()->count())->toBe(1)
            ->and(InventoryBalance::query()->where('store_id', $catalog['store_id'])->first()->quantity)->toBe('10.0000')
            ->and(StockAdjustment::query()->count())->toBe(0);

        $this->assertDatabaseHas('stock_transfers', [
            'id' => $transfer->json('data.id'),
            'status' => 'Draft',
        ]);
    });

    it('copies source layer cost onto the destination store', function () {
        actingAsTenantUser(permissions: [
            Permission::ProductsCreate,
            Permission::InventoryCreate,
            Permission::InventoryUpdate,
            Permission::InventoryView,
        ]);
        $catalog = receivedOpeningProduct(10, '820.0000');
        $destination = Store::factory()->create();

        $this->postJson('/api/v1/stock-transfers', [
            'product_variant_id' => $catalog['variant_id'],
            'from_store_id' => $catalog['store_id'],
            'to_store_id' => $destination->id,
            'transfer_date' => '2026-09-27',
            'quantity' => '10',
            'status' => 'Completed',
        ])->assertCreated()
            ->assertJsonPath('data.status', 'Completed')
            ->assertJsonPath('data.unit_cost', '820.0000')
            ->assertJsonPath('data.total_cost', '8200.0000')
            ->assertJsonPath('data.reference_number', 'TRF-000001');

        $source = InventoryLayer::query()->where('store_id', $catalog['store_id'])->first();
        $destinationLayer = InventoryLayer::query()->where('store_id', $destination->id)->first();

        expect($source->remaining_qty)->toBe('0.0000')
            ->and($destinationLayer->received_qty)->toBe('10.0000')
            ->and($destinationLayer->unit_cost)->toBe('820.0000')
            ->and($destinationLayer->source_type->value)->toBe('stock_transfer');

        $destinationBalance = InventoryBalance::query()->where('store_id', $destination->id)->first();
        expect($destinationBalance->quantity)->toBe('10.0000')
            ->and($destinationBalance->stock_value)->toBe('8200.0000');

        $foreign = Tenant::factory()->create();
        $transferId = StockMovement::query()->where('direction', StockDirection::Out)->first()->reference_id;
        actingAsTenantUser($foreign, [Permission::InventoryView]);

        $this->getJson('/api/v1/stock-transfers/'.$transferId)->assertNotFound();
        $this->getJson('/api/v1/inventory')->assertOk()->assertJsonCount(0, 'data');
    });

    it('restores fifo layers when a completed decrease is cancelled', function () {
        actingAsTenantUser(permissions: [
            Permission::ProductsCreate,
            Permission::InventoryCreate,
            Permission::InventoryUpdate,
        ]);
        $catalog = receivedOpeningProduct(10, '10.0000');

        $adjustment = $this->postJson('/api/v1/stock-adjustments', [
            'store_id' => $catalog['store_id'],
            'product_variant_id' => $catalog['variant_id'],
            'adjustment_date' => '2026-09-27',
            'adjustment_type' => 'Subtraction',
            'reason' => 'Count was short',
            'adjusted_quantity' => '7',
            'status' => 'Completed',
        ])->assertCreated();

        expect(InventoryBalance::query()->first()->quantity)->toBe('7.0000')
            ->and(InventoryLayer::query()->first()->remaining_qty)->toBe('7.0000');

        $this->putJson('/api/v1/stock-adjustments/'.$adjustment->json('data.id').'/status', [
            'status' => 'Cancelled',
        ])->assertOk()
            ->assertJsonPath('data.status', 'Cancelled');

        expect(InventoryBalance::query()->first()->quantity)->toBe('10.0000')
            ->and(InventoryLayer::query()->first()->remaining_qty)->toBe('10.0000')
            ->and(InventoryLayer::query()->first()->status)->toBe(InventoryLayerStatus::Open);
    });
});
