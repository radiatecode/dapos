<?php

use App\Enums\Permission;
use DA\Admin\Models\Tenant;
use DA\Inventory\Models\Product;
use DA\Inventory\Models\ProductVariant;
use DA\Inventory\Models\Purchase;
use DA\Inventory\Models\PurchaseOrderItem;
use DA\Inventory\Models\Store;
use DA\Inventory\Models\Supplier;

describe('authentication and authorization', function () {
    it('returns 401 when no token is provided', function (string $method, string $uri) {
        $this->json($method, $uri, purchasePayload())->assertUnauthorized();
    })->with([
        ['GET', '/api/v1/purchases'],
        ['POST', '/api/v1/purchases'],
        ['GET', '/api/v1/purchases/1'],
        ['PUT', '/api/v1/purchases/1'],
        ['PUT', '/api/v1/purchases/1/status'],
        ['POST', '/api/v1/purchases/delete'],
    ]);

    it('returns 403 when the user lacks the required permission', function () {
        actingAsTenantUser(permissions: [Permission::CategoriesView]);

        $this->getJson('/api/v1/purchases')->assertForbidden();
    });

    it('returns 403 when marking a purchase received without update permission', function () {
        $user = actingAsTenantUser(permissions: [Permission::PurchasingView]);
        $purchase = Purchase::factory()->create([
            'tenant_id' => $user->tenant_id,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $this->putJson('/api/v1/purchases/'.$purchase->id.'/status', [
            'status' => 'Received',
        ])->assertForbidden();
    });
});

describe('index and show', function () {
    it('lists purchases for the current tenant newest first', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::PurchasingView]);
        $older = Purchase::factory()->create(['tenant_id' => $tenant->id, 'po_number' => 'PO-OLDER']);
        $newer = Purchase::factory()->received()->create(['tenant_id' => $tenant->id, 'po_number' => 'PO-NEWER']);
        Purchase::withoutEvents(fn () => Purchase::factory()->create(['po_number' => 'PO-FOREIGN']));

        $this->getJson('/api/v1/purchases')
            ->assertOk()
            ->assertJsonPath('data.0.po_number', 'PO-NEWER')
            ->assertJsonPath('data.0.status', 'Received')
            ->assertJsonPath('data.1.id', $older->id)
            ->assertJsonMissing(['po_number' => 'PO-FOREIGN']);
    });

    it('filters purchases by pending and received status', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::PurchasingView]);
        Purchase::factory()->create(['tenant_id' => $tenant->id, 'po_number' => 'PO-PENDING']);
        Purchase::factory()->received()->create(['tenant_id' => $tenant->id, 'po_number' => 'PO-RECEIVED']);

        $this->getJson('/api/v1/purchases?status=Pending')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.po_number', 'PO-PENDING');

        $this->getJson('/api/v1/purchases?status=Received')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.po_number', 'PO-RECEIVED');
    });

    it('accepts a limit page size and ignores a blank status filter', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::PurchasingView]);
        Purchase::factory()->create(['tenant_id' => $tenant->id, 'po_number' => 'PO-ONE']);
        Purchase::factory()->received()->create(['tenant_id' => $tenant->id, 'po_number' => 'PO-TWO']);

        $this->getJson('/api/v1/purchases?limit=1&status=&payment_status=')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.per_page', 1)
            ->assertJsonPath('meta.total', 2);
    });

    it('returns 422 when the status filter is invalid', function () {
        actingAsTenantUser(permissions: [Permission::PurchasingView]);

        $this->getJson('/api/v1/purchases?status=Shipped')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');
    });

    it('returns 404 when tenant A requests a tenant B purchase', function () {
        actingAsTenantUser(permissions: [Permission::PurchasingView]);
        $foreign = Purchase::withoutEvents(fn () => Purchase::factory()->create());

        $this->getJson('/api/v1/purchases/'.$foreign->id)->assertNotFound();
    });
});

describe('store', function () {
    it('creates a purchase and calculates totals for the authenticated tenant', function () {
        $tenant = Tenant::factory()->create();
        $user = actingAsTenantUser($tenant, [Permission::PurchasingCreate]);
        $catalog = purchaseCatalog();

        $this->postJson('/api/v1/purchases', purchasePayload([
            'supplier_id' => $catalog['supplier']->id,
            'store_id' => $catalog['store']->id,
            'items' => [[
                'product_variant_id' => $catalog['variant']->id,
                'quantity' => '2',
                'unit_cost' => '10',
                'discount_amount' => '1',
                'tax_amount' => '0.5',
            ]],
            'created_by' => 999,
            'subtotal' => '1',
            'grand_total' => '1',
        ]))
            ->assertCreated()
            ->assertJsonPath('data.po_number', 'PO-'.now()->format('Ymd').'-0001')
            ->assertJsonPath('data.order_date', '2026-09-26')
            ->assertJsonPath('data.currency', 'BDT')
            ->assertJsonPath('data.subtotal', '20.0000')
            ->assertJsonPath('data.discount_amount', '2.0000')
            ->assertJsonPath('data.tax_amount', '1.0000')
            ->assertJsonPath('data.shipping_amount', '3.0000')
            ->assertJsonPath('data.grand_total', '21.5000')
            ->assertJsonPath('data.status', 'Pending')
            ->assertJsonPath('data.payment_status', 'Unpaid')
            ->assertJsonPath('data.notes', 'Weekly restock')
            ->assertJsonPath('data.created_by', $user->id)
            ->assertJsonPath('data.updated_by', $user->id)
            ->assertJsonPath('data.supplier.name', $catalog['supplier']->name)
            ->assertJsonPath('data.store.name', $catalog['store']->name)
            ->assertJsonPath('data.items.0.sku', $catalog['variant']->sku)
            ->assertJsonPath('data.items.0.total', '19.5000');

        $this->assertDatabaseHas('purchases', [
            'tenant_id' => $tenant->id,
            'po_number' => 'PO-'.now()->format('Ymd').'-0001',
            'created_by' => $user->id,
            'grand_total' => '21.5000',
            'status' => 'Pending',
        ]);
    });

    it('stores an explicit po number, ordered status, and payment status', function () {
        actingAsTenantUser(permissions: [Permission::PurchasingCreate]);
        $catalog = purchaseCatalog();

        $this->postJson('/api/v1/purchases', purchasePayload([
            'supplier_id' => $catalog['supplier']->id,
            'store_id' => $catalog['store']->id,
            'items' => [[
                'product_variant_id' => $catalog['variant']->id,
                'quantity' => '1',
                'unit_cost' => '5',
            ]],
            'po_number' => 'PO-CUSTOM',
            'status' => 'Ordered',
            'payment_status' => 'Partial',
            'discount_amount' => '0',
            'tax_amount' => '0',
            'shipping_amount' => '0',
        ]))
            ->assertCreated()
            ->assertJsonPath('data.po_number', 'PO-CUSTOM')
            ->assertJsonPath('data.status', 'Ordered')
            ->assertJsonPath('data.payment_status', 'Partial')
            ->assertJsonPath('data.grand_total', '5.0000');
    });

    it('returns 422 when items are missing', function () {
        actingAsTenantUser(permissions: [Permission::PurchasingCreate]);

        $this->postJson('/api/v1/purchases', purchasePayload(['items' => []]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['items' => 'At least one purchase item is required.']);
    });

    it('returns 422 when the variant belongs to another tenant', function () {
        actingAsTenantUser(permissions: [Permission::PurchasingCreate]);
        $catalog = purchaseCatalog();
        $foreignVariant = ProductVariant::withoutEvents(function () {
            $product = Product::withoutEvents(fn () => Product::factory()->create());

            return ProductVariant::factory()->create([
                'product_id' => $product->id,
                'tenant_id' => $product->tenant_id,
            ]);
        });

        $this->postJson('/api/v1/purchases', purchasePayload([
            'supplier_id' => $catalog['supplier']->id,
            'store_id' => $catalog['store']->id,
            'items' => [[
                'product_variant_id' => $foreignVariant->id,
                'quantity' => '1',
                'unit_cost' => '5',
            ]],
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['items.0.product_variant_id' => 'The selected product variant is invalid.']);
    });

    it('returns 422 when the po number is already used by the tenant', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::PurchasingCreate]);
        Purchase::factory()->create(['tenant_id' => $tenant->id, 'po_number' => 'PO-TAKEN']);
        $catalog = purchaseCatalog();

        $this->postJson('/api/v1/purchases', purchasePayload([
            'supplier_id' => $catalog['supplier']->id,
            'store_id' => $catalog['store']->id,
            'po_number' => 'PO-TAKEN',
            'items' => [[
                'product_variant_id' => $catalog['variant']->id,
                'quantity' => '1',
                'unit_cost' => '5',
            ]],
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['po_number' => 'The PO number has already been taken.']);
    });

    it('returns 422 when a line discount exceeds the line amount', function () {
        actingAsTenantUser(permissions: [Permission::PurchasingCreate]);
        $catalog = purchaseCatalog();

        $this->postJson('/api/v1/purchases', purchasePayload([
            'supplier_id' => $catalog['supplier']->id,
            'store_id' => $catalog['store']->id,
            'items' => [[
                'product_variant_id' => $catalog['variant']->id,
                'quantity' => '2',
                'unit_cost' => '10',
                'discount_amount' => '25',
            ]],
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['items.0.discount_amount' => 'The discount cannot exceed the line amount.']);
    });

    it('returns 422 when the grand total would be negative', function () {
        actingAsTenantUser(permissions: [Permission::PurchasingCreate]);
        $catalog = purchaseCatalog();

        $this->postJson('/api/v1/purchases', purchasePayload([
            'supplier_id' => $catalog['supplier']->id,
            'store_id' => $catalog['store']->id,
            'discount_amount' => '100',
            'tax_amount' => '0',
            'shipping_amount' => '0',
            'items' => [[
                'product_variant_id' => $catalog['variant']->id,
                'quantity' => '1',
                'unit_cost' => '10',
            ]],
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['discount_amount' => 'The grand total cannot be negative.']);
    });
});

describe('update', function () {
    it('replaces line items and recalculates totals', function () {
        $tenant = Tenant::factory()->create();
        $user = actingAsTenantUser($tenant, [Permission::PurchasingCreate, Permission::PurchasingUpdate]);
        $catalog = purchaseCatalog();
        $second = ProductVariant::factory()->create();

        $created = $this->postJson('/api/v1/purchases', purchasePayload([
            'supplier_id' => $catalog['supplier']->id,
            'store_id' => $catalog['store']->id,
            'po_number' => 'PO-EDIT',
            'discount_amount' => '0',
            'tax_amount' => '0',
            'shipping_amount' => '0',
            'items' => [
                [
                    'product_variant_id' => $catalog['variant']->id,
                    'quantity' => '1',
                    'unit_cost' => '4',
                ],
                [
                    'product_variant_id' => $second->id,
                    'quantity' => '1',
                    'unit_cost' => '6',
                ],
            ],
        ]))->assertCreated();

        $keptId = $created->json('data.items.1.id');

        $this->putJson('/api/v1/purchases/'.$created->json('data.id'), purchasePayload([
            'supplier_id' => $catalog['supplier']->id,
            'store_id' => $catalog['store']->id,
            'po_number' => 'PO-EDIT',
            'discount_amount' => '1',
            'tax_amount' => '0',
            'shipping_amount' => '0.5',
            'payment_status' => 'Paid',
            'items' => [[
                'id' => $keptId,
                'product_variant_id' => $second->id,
                'quantity' => '3',
                'unit_cost' => '6',
            ]],
        ]))
            ->assertOk()
            ->assertJsonPath('data.subtotal', '18.0000')
            ->assertJsonPath('data.grand_total', '17.5000')
            ->assertJsonPath('data.payment_status', 'Paid')
            ->assertJsonPath('data.updated_by', $user->id)
            ->assertJsonPath('data.created_by', $user->id)
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.id', $keptId)
            ->assertJsonPath('data.items.0.total', '18.0000');

        $this->assertDatabaseMissing('purchase_order_items', [
            'id' => $created->json('data.items.0.id'),
        ]);
    });

    it('returns 404 when updating another tenant purchase', function () {
        actingAsTenantUser(permissions: [Permission::PurchasingUpdate]);
        $foreign = Purchase::withoutEvents(fn () => Purchase::factory()->create());
        $catalog = purchaseCatalog();

        $this->putJson('/api/v1/purchases/'.$foreign->id, purchasePayload([
            'supplier_id' => $catalog['supplier']->id,
            'store_id' => $catalog['store']->id,
            'items' => [[
                'product_variant_id' => $catalog['variant']->id,
                'quantity' => '1',
                'unit_cost' => '5',
            ]],
        ]))->assertNotFound();
    });
});

describe('received and pending status', function () {
    it('marks a purchase received and then pending', function () {
        $user = actingAsTenantUser(permissions: [Permission::PurchasingCreate, Permission::PurchasingUpdate]);
        $catalog = purchaseCatalog();

        $purchaseId = $this->postJson('/api/v1/purchases', purchasePayload([
            'supplier_id' => $catalog['supplier']->id,
            'store_id' => $catalog['store']->id,
            'discount_amount' => '0',
            'tax_amount' => '0',
            'shipping_amount' => '0',
            'items' => [[
                'product_variant_id' => $catalog['variant']->id,
                'quantity' => '1',
                'unit_cost' => '8',
            ]],
        ]))->assertCreated()->json('data.id');

        $this->putJson('/api/v1/purchases/'.$purchaseId.'/status', ['status' => 'Received'])
            ->assertOk()
            ->assertJsonPath('data.status', 'Received')
            ->assertJsonPath('data.grand_total', '8.0000')
            ->assertJsonPath('data.updated_by', $user->id);

        $this->assertDatabaseHas('purchases', [
            'id' => $purchaseId,
            'status' => 'Received',
        ]);

        $this->putJson('/api/v1/purchases/'.$purchaseId.'/status', ['status' => 'Pending'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status' => 'A received purchase cannot change status.']);
    });

    it('returns 422 when the status is invalid', function () {
        $user = actingAsTenantUser(permissions: [Permission::PurchasingUpdate]);
        $purchase = Purchase::factory()->create([
            'tenant_id' => $user->tenant_id,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $this->putJson('/api/v1/purchases/'.$purchase->id.'/status', ['status' => 'Shipped'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status' => 'The selected status is invalid.']);
    });

    it('returns 404 when changing the status of another tenant purchase', function () {
        actingAsTenantUser(permissions: [Permission::PurchasingUpdate]);
        $foreign = Purchase::withoutEvents(fn () => Purchase::factory()->create());

        $this->putJson('/api/v1/purchases/'.$foreign->id.'/status', ['status' => 'Received'])
            ->assertNotFound();
    });
});

describe('destroy', function () {
    it('deletes purchases and their items for the current tenant', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::PurchasingDelete]);
        $purchase = Purchase::factory()->create(['tenant_id' => $tenant->id]);
        $item = PurchaseOrderItem::factory()->create([
            'purchase_order_id' => $purchase->id,
            'product_variant_id' => ProductVariant::factory()->create(['tenant_id' => $tenant->id])->id,
        ]);

        $this->postJson('/api/v1/purchases/delete', [
            'action' => 'delete',
            'ids' => [$purchase->id],
        ])
            ->assertOk()
            ->assertJsonPath('message', 'Purchase deleted successfully');

        $this->assertDatabaseMissing('purchases', ['id' => $purchase->id]);
        $this->assertDatabaseMissing('purchase_order_items', ['id' => $item->id]);
    });

    it('returns 400 when the delete action is invalid', function () {
        actingAsTenantUser(permissions: [Permission::PurchasingDelete]);

        $this->postJson('/api/v1/purchases/delete', ['action' => 'archive', 'ids' => [1]])
            ->assertBadRequest()
            ->assertJsonPath('message', 'Invalid action');
    });
});

/**
 * @return array{supplier: Supplier, store: Store, variant: ProductVariant}
 */
function purchaseCatalog(): array
{
    return [
        'supplier' => Supplier::factory()->create(),
        'store' => Store::factory()->create(),
        'variant' => ProductVariant::factory()->create(),
    ];
}
